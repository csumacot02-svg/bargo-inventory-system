<?php

namespace App\Http\Controllers;

use App\Models\InventoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ClientReleaseController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageInventory(), 403);

        $status = $request->string('status', 'released')->toString();
        $allowed = ['released', 'completed', 'approved', 'pending', 'all'];
        if (! in_array($status, $allowed, true)) {
            $status = 'released';
        }

        $search = trim($request->string('q')->toString());
        $from = $request->date('from');
        $to = $request->date('to');

        $base = InventoryRequest::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('client_name', 'like', $term)
                        ->orWhere('client_id_number', 'like', $term)
                        ->orWhere('client_office', 'like', $term)
                        ->orWhere('request_no', 'like', $term)
                        ->orWhere('purpose', 'like', $term);
                });
            })
            ->when($from, function ($q) use ($from, $status) {
                if ($status === 'released') {
                    $q->whereDate('released_at', '>=', $from);
                } else {
                    $q->whereDate('created_at', '>=', $from);
                }
            })
            ->when($to, function ($q) use ($to, $status) {
                if ($status === 'released') {
                    $q->whereDate('released_at', '<=', $to);
                } else {
                    $q->whereDate('created_at', '<=', $to);
                }
            });

        $releases = (clone $base)
            ->with(['requester', 'items.inventoryItem'])
            ->when($status === 'released', fn ($q) => $q->latest('released_at'), fn ($q) => $q->latest())
            ->paginate(15)
            ->withQueryString();

        $clientSummary = (clone $base)
            ->get(['client_name', 'client_id_number', 'client_office', 'released_at', 'created_at'])
            ->groupBy(function (InventoryRequest $row) {
                $name = trim((string) $row->client_name);

                return $name !== '' ? mb_strtolower($name) : '—';
            })
            ->map(function ($rows) {
                /** @var \Illuminate\Support\Collection<int, InventoryRequest> $rows */
                $firstNamed = $rows->first(fn (InventoryRequest $r) => filled(trim((string) $r->client_name)));
                $label = trim((string) ($firstNamed?->client_name ?? '—')) ?: '—';

                $last = $rows
                    ->map(fn (InventoryRequest $r) => $r->released_at ?? $r->created_at)
                    ->filter()
                    ->sortDesc()
                    ->first();

                return (object) [
                    'client_label' => $label,
                    'client_id_number' => $rows->pluck('client_id_number')->filter()->first(),
                    'client_office' => $rows->pluck('client_office')->filter()->first(),
                    'request_count' => $rows->count(),
                    'last_activity_at' => $last instanceof Carbon ? $last : null,
                ];
            })
            ->sortByDesc(fn ($row) => $row->last_activity_at?->timestamp ?? 0)
            ->take(100)
            ->values();

        return view('clients.index', [
            'releases' => $releases,
            'clientSummary' => $clientSummary,
            'status' => $status,
            'search' => $search,
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ]);
    }
}
