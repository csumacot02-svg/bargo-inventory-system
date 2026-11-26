@extends('layouts.app')

@section('title', $item->name)
@section('heading', $item->name)

@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('inventory.edit', $item) }}" class="btn-primary">Edit</a>
    <a href="{{ route('adjustments.create', ['item' => $item->id]) }}" class="btn-secondary">Adjust stock</a>
    <a href="{{ route('inventory.index') }}" class="btn-secondary">Back</a>
    @if(auth()->user()->canDeleteInventory())
        <form method="POST" action="{{ route('inventory.destroy', $item) }}" onsubmit="return confirm('Delete this item?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-accent">Delete</button>
        </form>
    @endif
</div>

<div class="panel grid gap-6 p-6 sm:grid-cols-2">
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Item Code</p>
        <p class="mt-1 text-lg font-semibold text-slsu-navy">{{ $item->item_code }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Category</p>
        <p class="mt-1 text-lg font-semibold text-slsu-navy">{{ $item->category?->name }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Quantity</p>
        <p class="mt-1 text-lg font-semibold text-slsu-navy">{{ number_format($item->quantity_on_hand, 2) }} {{ $item->unit }}</p>
        @if($item->is_perishable)
            <p class="mt-1 text-sm text-slsu-navy/70">Usable (not expired): <strong>{{ number_format($item->usableQuantity(), 2) }}</strong> · {{ $item->perishableStatus() }}</p>
        @endif
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Goods Type</p>
        <p class="mt-1">
            @if($item->is_perishable)
                <span class="badge badge-low">Perishable</span>
                @if($item->shelf_life_days)
                    <span class="text-sm text-slsu-navy/70"> · Shelf life {{ $item->shelf_life_days }} days</span>
                @endif
            @else
                <span class="badge badge-ok">Non-perishable</span>
            @endif
        </p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Reorder Level</p>
        <p class="mt-1 text-lg font-semibold text-slsu-navy">{{ number_format($item->reorder_level, 2) }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Condition</p>
        <p class="mt-1 capitalize">{{ $item->condition }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Supplier</p>
        <p class="mt-1">{{ $item->supplier?->name ?? '—' }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Location</p>
        <p class="mt-1">{{ $item->location ?? '—' }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Expiration</p>
        <p class="mt-1">{{ optional($item->expiration_date)->format('M d, Y') ?? '—' }}</p>
    </div>
    <div class="sm:col-span-2">
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Description</p>
        <p class="mt-1 text-slsu-navy/80">{{ $item->description ?: 'No description' }}</p>
    </div>
</div>

@if(isset($lots) && $lots->isNotEmpty())
    <section class="panel mt-6 overflow-hidden">
        <div class="border-b border-slsu-navy/10 px-5 py-4">
            <h2 class="font-display text-xl font-semibold text-slsu-navy">Lots (FIFO)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Expires</th>
                        <th>Received</th>
                        <th>Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lots as $lot)
                        <tr>
                            <td class="capitalize">{{ str_replace('_', ' ', $lot->source) }}</td>
                            <td>{{ optional($lot->expiration_date)->format('M d, Y') ?? '—' }}</td>
                            <td>{{ number_format($lot->quantity_received, 2) }}</td>
                            <td>{{ number_format($lot->quantity_remaining, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if(isset($movements))
    <section class="panel mt-6 overflow-hidden">
        <div class="border-b border-slsu-navy/10 px-5 py-4">
            <h2 class="font-display text-xl font-semibold text-slsu-navy">Stock movements</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Type</th>
                        <th>Change</th>
                        <th>After</th>
                        <th>By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $move)
                        <tr>
                            <td>{{ $move->created_at->format('M d, Y h:i A') }}</td>
                            <td class="capitalize">{{ str_replace('_', ' ', $move->type) }}</td>
                            <td>{{ $move->quantity_change }}</td>
                            <td>{{ $move->quantity_after }}</td>
                            <td>{{ $move->user?->name ?? '—' }}</td>
                            <td>{{ $move->notes }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slsu-navy/60">No movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $movements->links() }}</div>
    </section>
@endif
@endsection
