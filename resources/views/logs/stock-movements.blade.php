@extends('layouts.app')

@section('title', 'Stock Ledger')
@section('heading', 'Stock Movement Ledger')

@section('content')
<p class="mb-4 text-sm text-slsu-navy/70">Quantity changes posted to inventory: stock-in, release, payment, adjustments, spoilage, voids, and opening balances.</p>

<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('activity-logs.index') }}" class="btn-secondary">Activity</a>
    <a href="{{ route('logs.stock-movements') }}" class="btn-primary">Stock ledger</a>
</div>

<form method="GET" class="panel mb-5 flex flex-wrap items-end gap-2 p-4">
    <div>
        <label class="form-label">Search</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-input w-52" placeholder="Item, notes, type">
    </div>
    <div>
        <label class="form-label">Type</label>
        <select name="type" class="form-input w-44">
            <option value="">All</option>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ str_replace('_', ' ', $type) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="form-input">
    </div>
    <div>
        <label class="form-label">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="form-input">
    </div>
    <button class="btn-secondary" type="submit">Filter</button>
    <a href="{{ route('logs.stock-movements.export', request()->query()) }}" class="btn-primary">Export CSV</a>
</form>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>When</th>
                <th>Item</th>
                <th>Type</th>
                <th>Change</th>
                <th>Before</th>
                <th>After</th>
                <th>User</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($movements as $move)
                <tr>
                    <td class="whitespace-nowrap">{{ $move->created_at->timezone(config('app.timezone'))->format('M d, Y h:i A') }}</td>
                    <td>
                        @if($move->inventoryItem)
                            <a href="{{ route('inventory.show', $move->inventoryItem) }}" class="font-semibold text-slsu-blue hover:underline">{{ $move->inventoryItem->name }}</a>
                            <div class="text-xs text-slsu-navy/50">{{ $move->inventoryItem->item_code }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td class="capitalize">{{ str_replace('_', ' ', $move->type) }}</td>
                    <td>{{ number_format($move->quantity_change, 2) }}</td>
                    <td>{{ number_format($move->quantity_before, 2) }}</td>
                    <td>{{ number_format($move->quantity_after, 2) }}</td>
                    <td>{{ $move->user?->name ?? '—' }}</td>
                    <td>{{ $move->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-8 text-center text-slsu-navy/60">No stock movements yet. Record a stock-in, release, or adjustment.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $movements->links() }}</div>
@endsection
