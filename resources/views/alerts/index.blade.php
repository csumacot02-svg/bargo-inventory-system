@extends('layouts.app')

@section('title', 'Stock Alerts')
@section('heading', 'Inventory Alerts & Recommendations')

@section('content')
@php
    $critical = $outOfStock->count() + $expired->count();
    $warning = $lowStock->count() + $expiringSoon->count() + $damaged->count();
@endphp

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="stat-tile">
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Critical</p>
        <p class="font-display mt-2 text-3xl font-semibold text-slsu-maroon">{{ $critical }}</p>
        <p class="mt-1 text-xs text-slsu-navy/60">Out of stock + expired</p>
    </div>
    <div class="stat-tile">
        <p class="text-xs font-semibold tracking-wider text-amber-700 uppercase">Warnings</p>
        <p class="font-display mt-2 text-3xl font-semibold text-amber-700">{{ $warning }}</p>
        <p class="mt-1 text-xs text-slsu-navy/60">Low stock, expiring, damaged</p>
    </div>
    <div class="stat-tile">
        <p class="text-xs font-semibold tracking-wider text-slsu-navy uppercase">Recommendation</p>
        <p class="mt-2 text-sm leading-relaxed text-slsu-navy/80">
            @if($critical > 0)
                Prioritize replenishment for out-of-stock and remove/replace expired items.
            @elseif($lowStock->count() > 0)
                Prepare stock-in for items at or below reorder level.
            @else
                Inventory thresholds look healthy. Continue routine monitoring.
            @endif
        </p>
    </div>
</div>

<section class="panel mb-6 overflow-hidden">
    <div class="border-b border-slsu-navy/10 px-5 py-4">
        <h2 class="font-display text-xl font-semibold text-slsu-navy">Low / Out of Stock</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Reorder</th>
                    <th>Action needed</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lowStock as $item)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.show', $item) }}" class="font-semibold text-slsu-blue hover:underline">{{ $item->name }}</a>
                            <div class="text-xs text-slsu-navy/50">{{ $item->item_code }}</div>
                        </td>
                        <td>{{ $item->category?->name }}</td>
                        <td>{{ number_format($item->quantity_on_hand, 2) }} {{ $item->unit }}</td>
                        <td>{{ number_format($item->reorder_level, 2) }}</td>
                        <td>
                            @if($item->quantity_on_hand <= 0)
                                <span class="badge badge-out">Replenish immediately</span>
                            @else
                                <span class="badge badge-low">Schedule stock-in</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slsu-navy/60">No low-stock alerts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel mb-6 overflow-hidden">
    <div class="border-b border-slsu-navy/10 px-5 py-4">
        <h2 class="font-display text-xl font-semibold text-slsu-navy">Expiration Watch (30 days)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Expires</th>
                    <th>Qty</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expiringSoon->merge($expired) as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ optional($item->expiration_date)->format('M d, Y') }}</td>
                        <td>{{ number_format($item->quantity_on_hand, 2) }}</td>
                        <td>
                            @if($item->expiration_date->isPast())
                                <span class="badge badge-out">Expired</span>
                            @else
                                <span class="badge badge-low">Expiring soon</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-slsu-navy/60">No expiration alerts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel overflow-hidden">
    <div class="border-b border-slsu-navy/10 px-5 py-4">
        <h2 class="font-display text-xl font-semibold text-slsu-navy">Damaged / Poor Condition</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Condition</th>
                    <th>Qty</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($damaged as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td class="capitalize">{{ $item->condition }}</td>
                        <td>{{ number_format($item->quantity_on_hand, 2) }}</td>
                        <td><a href="{{ route('adjustments.create', ['item' => $item->id]) }}" class="text-sm font-semibold text-slsu-blue hover:underline">Adjust</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-slsu-navy/60">No damaged-item alerts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<section class="panel mt-6 overflow-hidden">
    <div class="border-b border-slsu-navy/10 px-5 py-4">
        <h2 class="font-display text-xl font-semibold text-slsu-navy">Overdue credit accounts</h2>
        <p class="mt-1 text-sm text-slsu-navy/60">Requests with at least one overdue installment (or past single due date).</p>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Request</th>
                    <th>Requester</th>
                    <th>Plan</th>
                    <th>Final / next due</th>
                    <th>Balance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($overdueCredits as $req)
                    <tr>
                        <td>{{ $req->request_no }}</td>
                        <td>{{ $req->requester?->name }}</td>
                        <td>{{ $req->installmentPlanLabel() ?? 'Credit' }}</td>
                        <td>{{ optional($req->due_date)->format('M d, Y') ?? '—' }}</td>
                        <td>₱{{ number_format($req->balanceDue(), 2) }}</td>
                        <td><a href="{{ route('requests.show', $req) }}" class="text-sm font-semibold text-slsu-blue hover:underline">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slsu-navy/60">No overdue credit accounts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel mt-6 overflow-hidden">
    <div class="border-b border-slsu-navy/10 px-5 py-4">
        <h2 class="font-display text-xl font-semibold text-slsu-navy">Overdue installments</h2>
        <p class="mt-1 text-sm text-slsu-navy/60">Individual daily/monthly payments past due.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Request</th>
                    <th>Requester</th>
                    <th>#</th>
                    <th>Due</th>
                    <th>Balance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($overdueInstallments as $row)
                    <tr>
                        <td>{{ $row->inventoryRequest?->request_no }}</td>
                        <td>{{ $row->inventoryRequest?->requester?->name }}</td>
                        <td>{{ $row->sequence }}</td>
                        <td>{{ $row->due_date->format('M d, Y') }}</td>
                        <td>₱{{ number_format($row->balanceDue(), 2) }}</td>
                        <td>
                            @if($row->inventoryRequest)
                                <a href="{{ route('requests.show', $row->inventoryRequest) }}" class="text-sm font-semibold text-slsu-blue hover:underline">Open</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slsu-navy/60">No overdue installments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
