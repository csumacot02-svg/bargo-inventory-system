<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InventoryItem;
use App\Models\InventoryRequest;
use App\Models\RequestPayment;
use App\Services\ClientPaymentNotificationService;
use App\Services\InstallmentScheduleService;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class InventoryRequestController extends Controller
{
    public function __construct(
        private InventoryStockService $stockService,
        private InstallmentScheduleService $installments,
        private ClientPaymentNotificationService $clientNotices,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->string('filter', 'all')->toString();
        $allowed = ['all', 'pending', 'approved', 'released', 'completed', 'history'];
        if (! in_array($filter, $allowed, true)) {
            $filter = 'all';
        }

        $search = trim($request->string('q')->toString());
        $from = $request->date('from');
        $to = $request->date('to');
        $statusFilter = $request->string('status')->toString();

        $requests = InventoryRequest::query()
            ->with(['requester', 'completer', 'items.inventoryItem', 'installments'])
            ->when(! $user->canViewAllRequests(), fn ($q) => $q->where('requester_id', $user->id))
            ->when($filter === 'history', function ($q) use ($statusFilter) {
                $q->whereIn('status', InventoryRequest::historyStatuses());
                if (in_array($statusFilter, InventoryRequest::historyStatuses(), true)) {
                    $q->where('status', $statusFilter);
                }
            })
            ->when(in_array($filter, InventoryRequest::statuses(), true), fn ($q) => $q->where('status', $filter))
            ->when($filter === 'all', fn ($q) => $q->whereIn('status', InventoryRequest::activeStatuses()))
            ->when($search !== '', function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('request_no', 'like', $term)
                        ->orWhere('client_name', 'like', $term)
                        ->orWhere('purpose', 'like', $term)
                        ->orWhereHas('requester', fn ($rq) => $rq->where('name', 'like', $term));
                });
            })
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($filter === 'completed', fn ($q) => $q->latest('completed_at'))
            ->when($filter === 'released', fn ($q) => $q->latest('released_at'))
            ->when(! in_array($filter, ['completed', 'released'], true), fn ($q) => $q->latest())
            ->paginate(12)
            ->withQueryString();

        $heading = match ($filter) {
            'pending' => 'Pending Requests',
            'approved' => 'Approved Requests',
            'released' => 'Released Requests',
            'completed' => 'Completed Requests',
            'history' => 'Request History',
            default => 'All Active Requests',
        };

        return view('requests.index', [
            'requests' => $requests,
            'filter' => $filter,
            'heading' => $heading,
            'search' => $search,
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'statusFilter' => $statusFilter,
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->canSubmitRequests(), 403);

        return view('requests.create', [
            'items' => InventoryItem::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->filter(fn (InventoryItem $item) => $item->usableQuantity() > 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canSubmitRequests(), 403);

        $data = $request->validate([
            'purpose' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_id_number' => ['nullable', 'string', 'max:50'],
            'client_office' => ['nullable', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'payment_type' => ['nullable', 'in:cash,credit'],
            'due_date' => ['nullable', 'date'],
            'installment_frequency' => ['nullable', 'in:once,daily,monthly'],
            'installment_count' => ['nullable', 'integer', 'min:1', 'max:90'],
            'installment_start_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity_requested' => ['required', 'numeric', 'min:0.01'],
        ]);

        if (($data['payment_type'] ?? null) === 'credit') {
            $frequency = $data['installment_frequency'] ?? 'once';
            $count = (int) ($data['installment_count'] ?? 1);

            if (in_array($frequency, ['daily', 'monthly'], true) && $count < 2) {
                return back()->withInput()->withErrors([
                    'installment_count' => 'Choose at least 2 installments for daily or monthly plans.',
                ]);
            }

            if ($frequency === 'daily' && $count > 90) {
                return back()->withInput()->withErrors([
                    'installment_count' => 'Daily plans allow up to 90 installments.',
                ]);
            }

            if ($frequency === 'monthly' && $count > 36) {
                return back()->withInput()->withErrors([
                    'installment_count' => 'Monthly plans allow up to 36 installments.',
                ]);
            }

            if ($frequency === 'once' && empty($data['due_date'])) {
                return back()->withInput()->withErrors([
                    'due_date' => 'Set a due date for single-payment credit.',
                ]);
            }

            if (in_array($frequency, ['daily', 'monthly'], true) && empty($data['installment_start_date'])) {
                return back()->withInput()->withErrors([
                    'installment_start_date' => 'Set the first installment date.',
                ]);
            }
        }

        foreach ($data['items'] as $row) {
            $item = InventoryItem::query()->findOrFail($row['inventory_item_id']);
            if ($item->is_perishable && $item->isExpired()) {
                return back()->withInput()->with('error', "{$item->name} is perishable and expired — not requestable.");
            }
            if ($item->usableQuantity() < (float) $row['quantity_requested']) {
                return back()->withInput()->with('error', "Insufficient usable stock for {$item->name}.");
            }
        }

        $inventoryRequest = DB::transaction(function () use ($data, $request) {
            $paymentType = $data['payment_type'] ?? null;
            $frequency = $paymentType === 'credit'
                ? ($data['installment_frequency'] ?? 'once')
                : null;
            $count = $paymentType === 'credit'
                ? (int) ($data['installment_count'] ?? 1)
                : null;

            if ($frequency === 'once') {
                $count = 1;
            }

            $inventoryRequest = InventoryRequest::create([
                'request_no' => 'REQ-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
                'requester_id' => $request->user()->id,
                'status' => 'pending',
                'purpose' => $data['purpose'],
                'client_name' => $data['client_name'],
                'client_id_number' => $data['client_id_number'] ?? null,
                'client_office' => $data['client_office'] ?? null,
                'client_email' => $data['client_email'],
                'payment_type' => $paymentType,
                'payment_status' => match ($paymentType) {
                    'credit', 'cash' => 'unpaid',
                    default => null,
                },
                'due_date' => $data['due_date'] ?? null,
                'installment_frequency' => $frequency,
                'installment_count' => $count,
                'installment_start_date' => $paymentType === 'credit'
                    ? ($data['installment_start_date'] ?? $data['due_date'] ?? null)
                    : null,
                'remarks' => $data['remarks'] ?? null,
                'stock_deducted' => false,
            ]);

            foreach ($data['items'] as $row) {
                $inventoryRequest->items()->create($row);
            }

            $inventoryRequest->load('items.inventoryItem');
            $this->installments->syncSchedule($inventoryRequest);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'request_submit',
                'module' => 'requests',
                'description' => 'Submitted request '.$inventoryRequest->request_no,
                'ip_address' => $request->ip(),
            ]);

            return $inventoryRequest;
        });

        return redirect()->route('requests.show', $inventoryRequest)->with('success', 'Request submitted for approval.');
    }

    public function show(Request $request, InventoryRequest $inventoryRequest): View
    {
        abort_unless($request->user()->canViewRequest($inventoryRequest), 403);

        $inventoryRequest->load([
            'requester',
            'reviewer',
            'completer',
            'canceller',
            'items.inventoryItem',
            'installments',
            'payments.recorder',
        ]);

        return view('requests.show', compact('inventoryRequest'));
    }

    public function updateClient(Request $request, InventoryRequest $inventoryRequest): RedirectResponse
    {
        abort_unless(
            $request->user()->canActOnRequest($inventoryRequest)
                || (int) $inventoryRequest->requester_id === (int) $request->user()->id,
            403
        );

        if ($inventoryRequest->isReadOnly()) {
            return back()->with('error', 'Cannot update client on a finalized request.');
        }

        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_id_number' => ['nullable', 'string', 'max:50'],
            'client_office' => ['nullable', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
        ]);

        $inventoryRequest->update([
            'client_name' => $data['client_name'],
            'client_id_number' => $data['client_id_number'] ?? null,
            'client_office' => $data['client_office'] ?? null,
            'client_email' => $data['client_email'],
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'request_client_update',
            'module' => 'requests',
            'description' => 'Updated client on '.$inventoryRequest->request_no.' to '.$data['client_name'],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Client information saved.');
    }

    public function approve(Request $request, InventoryRequest $inventoryRequest): RedirectResponse
    {
        abort_unless($request->user()->canActOnRequest($inventoryRequest), 403);

        if ($inventoryRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        try {
            DB::transaction(function () use ($request, $inventoryRequest) {
                foreach ($inventoryRequest->items as $line) {
                    $item = InventoryItem::query()->lockForUpdate()->findOrFail($line->inventory_item_id);

                    if ($item->usableQuantity() < (float) $line->quantity_requested) {
                        throw new RuntimeException("Insufficient usable stock for {$item->name}.");
                    }

                    $line->update(['quantity_approved' => $line->quantity_requested]);
                }

                $inventoryRequest->update([
                    'status' => 'approved',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);

                $inventoryRequest->load('items.inventoryItem');
                $this->installments->syncSchedule($inventoryRequest->fresh(['items.inventoryItem', 'installments']));

                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'request_approve',
                    'module' => 'requests',
                    'description' => 'Approved request '.$inventoryRequest->request_no,
                    'ip_address' => $request->ip(),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Request approved. Confirm payment and/or release to update stock.');
    }

    public function reject(Request $request, InventoryRequest $inventoryRequest): RedirectResponse
    {
        abort_unless($request->user()->canActOnRequest($inventoryRequest), 403);

        if ($inventoryRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $request->validate(['remarks' => ['nullable', 'string']]);

        $inventoryRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'remarks' => $request->input('remarks', $inventoryRequest->remarks),
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'request_reject',
            'module' => 'requests',
            'description' => 'Rejected request '.$inventoryRequest->request_no,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Request rejected. Stock unchanged.');
    }

    public function release(Request $request, InventoryRequest $inventoryRequest): RedirectResponse
    {
        abort_unless($request->user()->canActOnRequest($inventoryRequest), 403);

        if ($inventoryRequest->status !== 'approved') {
            return back()->with('error', 'Only approved requests can be released.');
        }

        try {
            DB::transaction(function () use ($request, $inventoryRequest) {
                $this->stockService->applyRequestStockOut($inventoryRequest, $request->user(), 'release');

                $updates = [
                    'status' => 'released',
                    'released_at' => now(),
                ];

                if ($inventoryRequest->payment_type === 'cash') {
                    $updates['payment_status'] = 'paid';
                    $updates['paid_at'] = now();
                    $updates['amount_paid'] = $inventoryRequest->totalAmount();
                }

                $inventoryRequest->update($updates);

                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'request_release',
                    'module' => 'requests',
                    'description' => 'Released '.$inventoryRequest->request_no.' (stock auto-deducted)',
                    'ip_address' => $request->ip(),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $fresh = $inventoryRequest->fresh(['items.inventoryItem']);
        if ($fresh && $fresh->payment_status === 'paid') {
            $this->clientNotices->sendFullyPaidNotice($fresh);
        }

        return redirect()
            ->route('requests.release-slip', $fresh)
            ->with('success', 'Items released and inventory stock updated automatically.');
    }

    /**
     * Confirm payment — supports cash/credit and installment waterfall.
     */
    public function confirmPayment(Request $request, InventoryRequest $inventoryRequest): RedirectResponse
    {
        abort_unless($request->user()->canActOnRequest($inventoryRequest), 403);

        if (! in_array($inventoryRequest->status, ['approved', 'released'], true)) {
            return back()->with('error', 'Payment can only be confirmed for approved or released requests.');
        }

        if ($inventoryRequest->payment_status === 'paid') {
            return back()->with('error', 'Payment already confirmed.');
        }

        $inventoryRequest->load(['items.inventoryItem', 'installments']);
        $balance = $inventoryRequest->balanceDue();

        $data = $request->validate([
            'or_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('request_payments', 'or_number'),
                Rule::unique('inventory_requests', 'or_number')->ignore($inventoryRequest->id),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = round((float) $data['amount'], 2);

        if ($balance > 0 && $amount > $balance + 0.009) {
            return back()->withInput()->with('error', 'Amount exceeds remaining balance of ₱'.number_format($balance, 2).'.');
        }

        $orNumber = trim((string) ($data['or_number'] ?? '')) ?: InventoryRequest::nextOrNumber();

        try {
            DB::transaction(function () use ($request, $inventoryRequest, $amount, $orNumber, $balance, $data) {
                RequestPayment::query()->create([
                    'inventory_request_id' => $inventoryRequest->id,
                    'recorded_by' => $request->user()->id,
                    'or_number' => $orNumber,
                    'amount' => $amount,
                    'notes' => $data['notes'] ?? null,
                    'paid_at' => now(),
                ]);

                if ($inventoryRequest->installments()->exists()) {
                    $this->installments->applyPayment($inventoryRequest, $amount);
                }

                $newPaid = round((float) $inventoryRequest->amount_paid + $amount, 2);
                $fullyPaid = $balance <= 0 || $newPaid + 0.009 >= $inventoryRequest->totalAmount();

                $updates = [
                    'amount_paid' => $newPaid,
                    'or_number' => $orNumber,
                    'payment_status' => $fullyPaid ? 'paid' : 'partial',
                ];

                if ($fullyPaid) {
                    $updates['paid_at'] = now();
                    $this->stockService->applyRequestStockOut($inventoryRequest, $request->user(), 'payment');

                    if ($inventoryRequest->status === 'approved') {
                        $updates['status'] = 'released';
                        $updates['released_at'] = now();
                    }
                }

                $inventoryRequest->update($updates);

                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'payment_confirm',
                    'module' => 'requests',
                    'description' => ($fullyPaid ? 'Payment completed' : 'Installment/partial payment').' for '.$inventoryRequest->request_no
                        .' OR '.$orNumber.' ₱'.number_format($amount, 2),
                    'ip_address' => $request->ip(),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $inventoryRequest->refresh()->loadMissing('items.inventoryItem');

        if ($inventoryRequest->payment_status === 'paid') {
            $this->clientNotices->sendFullyPaidNotice($inventoryRequest);
        }

        if ($inventoryRequest->payment_status === 'paid' && $inventoryRequest->status === 'released') {
            return redirect()
                ->route('requests.release-slip', $inventoryRequest)
                ->with('success', 'Payment recorded (OR '.$orNumber.'). Stock updated and receipt ready.');
        }

        return back()->with('success', 'Payment of ₱'.number_format($amount, 2).' recorded (OR '.$orNumber.'). Remaining balance ₱'.number_format($inventoryRequest->balanceDue(), 2).'.');
    }

    public function releaseSlip(
        InventoryRequest $inventoryRequest,
        \App\Services\OrderOfPaymentDocumentBuilder $documentBuilder,
        \App\Services\BargoDocumentTemplateService $documentTemplates,
    ): View {
        abort_unless(auth()->user()->canViewRequest($inventoryRequest), 403);

        abort_unless(in_array($inventoryRequest->status, [
            InventoryRequest::STATUS_RELEASED,
            InventoryRequest::STATUS_COMPLETED,
        ], true), 404);

        $data = $documentBuilder->build($inventoryRequest);

        return $documentTemplates->render('documents.order-of-payment', $data);
    }

    public function downloadPaymentOrderExcel(
        InventoryRequest $inventoryRequest,
        \App\Services\OrderOfPaymentExcelExporter $exporter,
    ): \Symfony\Component\HttpFoundation\StreamedResponse {
        abort_unless(auth()->user()->canViewRequest($inventoryRequest), 403);

        abort_unless(in_array($inventoryRequest->status, [
            InventoryRequest::STATUS_RELEASED,
            InventoryRequest::STATUS_COMPLETED,
        ], true), 404);

        return $exporter->download($inventoryRequest);
    }

    public function complete(Request $request, InventoryRequest $inventoryRequest): RedirectResponse
    {
        abort_unless($request->user()->canActOnRequest($inventoryRequest), 403);

        if (! $inventoryRequest->canBeCompleted()) {
            return back()->with('error', 'Only released requests can be marked as completed.');
        }

        DB::transaction(function () use ($request, $inventoryRequest) {
            $inventoryRequest->update([
                'status' => InventoryRequest::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => $request->user()->id,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'request_complete',
                'module' => 'requests',
                'description' => 'Marked '.$inventoryRequest->request_no.' as completed (no additional stock change)',
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()
            ->route('requests.show', $inventoryRequest->fresh())
            ->with('success', 'Request marked as completed. Stock was not changed again.');
    }

    public function cancel(Request $request, InventoryRequest $inventoryRequest): RedirectResponse
    {
        abort_unless($request->user()->canActOnRequest($inventoryRequest), 403);

        if (! $inventoryRequest->canBeCancelled()) {
            return back()->with('error', 'Only pending or approved requests can be cancelled.');
        }

        $data = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $inventoryRequest, $data) {
            $inventoryRequest->update([
                'status' => InventoryRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
                'remarks' => $data['remarks'] ?? $inventoryRequest->remarks,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'request_cancel',
                'module' => 'requests',
                'description' => 'Cancelled request '.$inventoryRequest->request_no,
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()
            ->route('requests.show', $inventoryRequest->fresh())
            ->with('success', 'Request cancelled. Stock unchanged.');
    }
}
