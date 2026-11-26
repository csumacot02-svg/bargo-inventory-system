@extends('layouts.app')

@section('title', $inventoryRequest->request_no)
@section('heading', 'Request '.$inventoryRequest->request_no)

@section('content')
<div class="panel mb-6 grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Client</p>
        <p class="mt-1 font-semibold">{{ $inventoryRequest->clientDisplayName() }}</p>
        @if($inventoryRequest->client_email)
            <p class="mt-0.5 text-sm text-slsu-navy/70">{{ $inventoryRequest->client_email }}</p>
        @endif
        @if($inventoryRequest->client_id_number)
            <p class="mt-0.5 text-sm text-slsu-navy/70">ID {{ $inventoryRequest->client_id_number }}</p>
        @endif
        @if($inventoryRequest->client_office)
            <p class="mt-0.5 text-sm text-slsu-navy/70">{{ $inventoryRequest->client_office }}</p>
        @endif
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Recorded by</p>
        <p class="mt-1 font-semibold">{{ $inventoryRequest->requester?->name }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Status</p>
        <p class="mt-1"><span class="badge {{ $inventoryRequest->statusBadgeClass() }}">{{ $inventoryRequest->statusLabel() }}</span></p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Purpose</p>
        <p class="mt-1 font-semibold">{{ $inventoryRequest->purpose }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Payment</p>
        <p class="mt-1 capitalize">{{ $inventoryRequest->payment_type ?? '—' }} / {{ $inventoryRequest->payment_status ?? '—' }}</p>
        @if($inventoryRequest->installmentPlanLabel())
            <p class="mt-1 text-sm text-slsu-navy/70">{{ $inventoryRequest->installmentPlanLabel() }}</p>
        @endif
        @if($inventoryRequest->or_number)
            <p class="mt-1 text-sm">Latest OR {{ $inventoryRequest->or_number }} · paid ₱{{ number_format($inventoryRequest->amount_paid, 2) }}</p>
        @endif
        <p class="mt-1 text-sm text-slsu-navy/70">Balance ₱{{ number_format($inventoryRequest->balanceDue(), 2) }} of ₱{{ number_format($inventoryRequest->totalAmount(), 2) }}</p>
        @if($inventoryRequest->isOverdue())
            <p class="mt-1 text-sm font-semibold text-slsu-maroon">Overdue installment(s)</p>
        @endif
        @if($next = $inventoryRequest->nextInstallmentDue())
            <p class="mt-1 text-sm">Next due: #{{ $next->sequence }} on {{ $next->due_date->format('M d, Y') }} · ₱{{ number_format($next->balanceDue(), 2) }}</p>
        @endif
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Stock Deducted</p>
        <p class="mt-1">{{ $inventoryRequest->stock_deducted ? 'Yes (inventory updated)' : 'Not yet' }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Reviewed By</p>
        <p class="mt-1">{{ $inventoryRequest->reviewer?->name ?? '—' }}</p>
        @if($inventoryRequest->reviewed_at)
            <p class="mt-0.5 text-sm text-slsu-navy/70">{{ $inventoryRequest->reviewed_at->format('M d, Y h:i A') }}</p>
        @endif
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Released</p>
        <p class="mt-1">{{ optional($inventoryRequest->released_at)->format('M d, Y h:i A') ?? '—' }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Completed</p>
        <p class="mt-1">{{ optional($inventoryRequest->completed_at)->format('M d, Y h:i A') ?? '—' }}</p>
        @if($inventoryRequest->completer)
            <p class="mt-0.5 text-sm text-slsu-navy/70">by {{ $inventoryRequest->completer->name }}</p>
        @endif
    </div>
    @if($inventoryRequest->status === 'cancelled')
        <div>
            <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Cancelled</p>
            <p class="mt-1">{{ optional($inventoryRequest->cancelled_at)->format('M d, Y h:i A') ?? '—' }}</p>
            @if($inventoryRequest->canceller)
                <p class="mt-0.5 text-sm text-slsu-navy/70">by {{ $inventoryRequest->canceller->name }}</p>
            @endif
        </div>
    @endif
</div>

@if($inventoryRequest->isReadOnly())
    <div class="mb-4 rounded-lg border border-slsu-navy/15 bg-slsu-cream/60 px-4 py-3 text-sm text-slsu-navy">
        This request is <strong>{{ $inventoryRequest->statusLabel() }}</strong> and read-only. Transaction details are preserved in Request History.
    </div>
@elseif(
    auth()->user()->canActOnRequest($inventoryRequest)
    || (int) $inventoryRequest->requester_id === (int) auth()->id()
)
    <form method="POST" action="{{ route('requests.update-client', $inventoryRequest) }}" class="panel mb-5 grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        @csrf
        @method('PATCH')
        <div>
            <label class="form-label">Client name</label>
            <input type="text" name="client_name" value="{{ old('client_name', $inventoryRequest->client_name) }}" required class="form-input" placeholder="Name on Payment Order">
            @error('client_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Client email</label>
            <input type="email" name="client_email" value="{{ old('client_email', $inventoryRequest->client_email) }}" required class="form-input">
            @error('client_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Client ID No.</label>
            <input type="text" name="client_id_number" value="{{ old('client_id_number', $inventoryRequest->client_id_number) }}" class="form-input">
        </div>
        <div class="flex gap-2 sm:items-end">
            <div class="min-w-0 flex-1">
                <label class="form-label">Office / Dept</label>
                <input type="text" name="client_office" value="{{ old('client_office', $inventoryRequest->client_office) }}" class="form-input">
            </div>
            <button type="submit" class="btn-secondary shrink-0">Save client</button>
        </div>
    </form>
@endif

@unless($inventoryRequest->isReadOnly())
<div class="mb-4 rounded-lg border border-slsu-blue/20 bg-slsu-blue/10 px-4 py-3 text-sm text-slsu-navy">
    Stock updates automatically on <strong>Release</strong> or when credit is <strong>fully paid</strong> (whichever completes first). Stock is never deducted twice. After release, use <strong>Mark as Completed</strong> when the client has received the items.
</div>
@endunless

<div class="table-shell mb-6 overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Type</th>
                <th>Requested</th>
                <th>Approved</th>
                <th>On Hand / Usable</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventoryRequest->items as $line)
                @php $item = $line->inventoryItem; @endphp
                <tr>
                    <td>
                        {{ $item?->name }}
                        @if($item?->is_perishable)
                            <div class="text-xs text-slsu-maroon">Expires {{ optional($item->expiration_date)->format('M d, Y') ?? 'N/A' }}</div>
                        @endif
                    </td>
                    <td>
                        @if($item?->is_perishable)
                            <span class="badge badge-low">Perishable</span>
                        @else
                            <span class="badge badge-ok">Non-perishable</span>
                        @endif
                    </td>
                    <td>{{ number_format($line->quantity_requested, 2) }}</td>
                    <td>{{ $line->quantity_approved !== null ? number_format($line->quantity_approved, 2) : '—' }}</td>
                    <td>{{ number_format($item?->quantity_on_hand ?? 0, 2) }} / {{ number_format($item?->usableQuantity() ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($inventoryRequest->installments->isNotEmpty())
    <section class="panel mb-6 overflow-hidden">
        <div class="border-b border-slsu-navy/10 px-5 py-4">
            <h2 class="font-display text-xl font-semibold text-slsu-navy">Installment schedule</h2>
            <p class="mt-1 text-sm text-slsu-navy/60">{{ $inventoryRequest->installmentPlanLabel() }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Due date</th>
                        <th>Amount due</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventoryRequest->installments as $row)
                        <tr class="{{ $row->isOverdue() ? 'bg-slsu-maroon/5' : '' }}">
                            <td>{{ $row->sequence }}</td>
                            <td>{{ $row->due_date->format('M d, Y') }}</td>
                            <td>₱{{ number_format($row->amount_due, 2) }}</td>
                            <td>₱{{ number_format($row->amount_paid, 2) }}</td>
                            <td>₱{{ number_format($row->balanceDue(), 2) }}</td>
                            <td>
                                @if($row->isOverdue())
                                    <span class="badge badge-low">Overdue</span>
                                @elseif($row->status === 'paid')
                                    <span class="badge badge-ok">Paid</span>
                                @elseif($row->status === 'partial')
                                    <span class="badge badge-pending">Partial</span>
                                @else
                                    <span class="badge badge-pending">Unpaid</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if($inventoryRequest->payments->isNotEmpty())
    <section class="panel mb-6 overflow-hidden">
        <div class="border-b border-slsu-navy/10 px-5 py-4">
            <h2 class="font-display text-xl font-semibold text-slsu-navy">Payment history</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>OR number</th>
                        <th>Amount</th>
                        <th>Recorded by</th>
                        <th>Paid at</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventoryRequest->payments as $payment)
                        <tr>
                            <td class="font-medium">{{ $payment->or_number }}</td>
                            <td>₱{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->recorder?->name ?? '—' }}</td>
                            <td>{{ $payment->paid_at->format('M d, Y h:i A') }}</td>
                            <td>{{ $payment->notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if(auth()->user()->canActOnRequest($inventoryRequest) && ! $inventoryRequest->isReadOnly())
    <div class="flex flex-wrap gap-3">
        @if($inventoryRequest->status === 'pending')
            <form method="POST" action="{{ route('requests.approve', $inventoryRequest) }}">
                @csrf
                <button class="btn-success" type="submit">Approve</button>
            </form>
            <form method="POST" action="{{ route('requests.reject', $inventoryRequest) }}" class="flex flex-wrap items-center gap-2">
                @csrf
                <input type="text" name="remarks" placeholder="Rejection remarks" class="form-input max-w-xs">
                <button class="btn-accent" type="submit">Reject</button>
            </form>
        @endif

        @if($inventoryRequest->canBeCancelled())
            <form method="POST" action="{{ route('requests.cancel', $inventoryRequest) }}" class="flex flex-wrap items-center gap-2" onsubmit="return confirm('Cancel this request? Stock will not change.');">
                @csrf
                <input type="text" name="remarks" placeholder="Cancellation remarks" class="form-input max-w-xs">
                <button class="btn-secondary" type="submit">Cancel request</button>
            </form>
        @endif

        @if($inventoryRequest->status === 'approved')
            <form method="POST" action="{{ route('requests.release', $inventoryRequest) }}">
                @csrf
                <button class="btn-primary" type="submit">Release Items (update stock)</button>
            </form>
        @endif

        @if($inventoryRequest->canBeCompleted())
            <form method="POST" action="{{ route('requests.complete', $inventoryRequest) }}" onsubmit="return confirm('Are you sure you want to mark this request as completed? Please confirm that all requested items have been received and the transaction has been finalized.');">
                @csrf
                <button class="btn-success" type="submit">Mark as Completed</button>
            </form>
        @endif

        @if($inventoryRequest->needsPaymentConfirmation())
            <form method="POST" action="{{ route('requests.confirm-payment', $inventoryRequest) }}" class="flex flex-wrap items-end gap-2 rounded-lg border border-slsu-navy/10 bg-white p-4">
                @csrf
                <div>
                    <label class="form-label">OR number</label>
                    <input type="text" name="or_number" value="{{ old('or_number') }}" class="form-input w-40" placeholder="Auto if blank">
                </div>
                <div>
                    <label class="form-label">Amount (₱)</label>
                    @php
                        $suggest = $inventoryRequest->nextInstallmentDue()?->balanceDue() ?? $inventoryRequest->balanceDue();
                    @endphp
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $suggest) }}" required class="form-input w-36">
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-input w-48" placeholder="e.g. 2nd monthly">
                </div>
                <button class="btn-success" type="submit">Record installment / payment</button>
            </form>
        @endif
    </div>
@endif

@if(in_array($inventoryRequest->status, ['released', 'completed'], true))
    <div class="mt-4 flex flex-wrap gap-3">
        <a href="{{ route('requests.release-slip', $inventoryRequest) }}" class="btn-primary" target="_blank">Print Order of Payment</a>
        <a href="{{ route('requests.payment-order-excel', $inventoryRequest) }}" class="btn-secondary">Download Excel</a>
    </div>
@endif

<div class="mt-6 flex flex-wrap gap-3">
    <a href="{{ route('requests.index', ['filter' => $inventoryRequest->isFinalized() ? 'history' : ($inventoryRequest->status === 'completed' ? 'completed' : 'all')]) }}" class="btn-secondary">Back to requests</a>
    @if($inventoryRequest->status === 'completed')
        <a href="{{ route('requests.completed') }}" class="btn-secondary">Completed list</a>
    @endif
</div>
@endsection
