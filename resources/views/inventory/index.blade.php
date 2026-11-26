@extends('layouts.app')

@section('title', 'Inventory')
@section('heading', 'Inventory Management')

@section('content')
<p class="page-lede">
    Search and filter the BARGO catalog. Open an item to adjust details, review stock history, or prepare replenishment.
</p>

<form method="GET" class="filter-bar">
    <div class="filter-bar__fields">
        <div class="min-w-[12rem] flex-1">
            <label class="form-label" for="q">Search</label>
            <input id="q" type="text" name="q" value="{{ request('q') }}" placeholder="Name, code, or barcode" class="form-input">
        </div>
        <div class="w-full sm:w-44">
            <label class="form-label" for="category">Category</label>
            <select id="category" name="category" class="form-input">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-36">
            <label class="form-label" for="status">Stock</label>
            <select id="status" name="status" class="form-input">
                <option value="">All stock</option>
                <option value="low" @selected(request('status') === 'low')>Low stock</option>
            </select>
        </div>
        <button class="btn-secondary" type="submit">Apply</button>
        @if(request()->filled('q') || request()->filled('category') || request()->filled('status'))
            <a href="{{ route('inventory.index') }}" class="btn-secondary">Clear</a>
        @endif
    </div>
    <a href="{{ route('inventory.create') }}" class="btn-primary shrink-0 self-stretch sm:self-end">Add Item</a>
</form>

<div class="result-meta">
    <p>
        @if ($items->total() === 0)
            No items match these filters
        @else
            Showing
            <strong class="text-slsu-navy">{{ $items->firstItem() }}–{{ $items->lastItem() }}</strong>
            of <strong class="text-slsu-navy">{{ $items->total() }}</strong>
            {{ $items->total() === 1 ? 'item' : 'items' }}
        @endif
    </p>
    @if(request('status') === 'low')
        <span class="badge badge-low">Low stock filter on</span>
    @endif
</div>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>On hand</th>
                <th>Condition</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @php $status = $item->stockStatus(); @endphp
                <tr>
                    <td>
                        <a href="{{ route('inventory.show', $item) }}" class="inventory-name hover:text-slsu-blue">{{ $item->name }}</a>
                        <p class="inventory-code">{{ $item->item_code }}</p>
                        @if($item->is_perishable)
                            <span class="badge badge-low mt-1">Perishable</span>
                        @endif
                    </td>
                    <td class="text-slsu-navy/70">{{ $item->category?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap">
                        <span class="font-semibold text-slsu-navy">{{ number_format($item->quantity_on_hand, 2) }}</span>
                        <span class="text-slsu-navy/45">{{ $item->unit }}</span>
                        <p class="mt-0.5 text-[11px] text-slsu-navy/40">Reorder {{ number_format($item->reorder_level, 0) }}</p>
                    </td>
                    <td class="capitalize text-slsu-navy/70">{{ $item->condition }}</td>
                    <td>
                        <span class="badge {{ $status === 'ok' ? 'badge-ok' : (in_array($status, ['low', 'expired'], true) ? 'badge-low' : 'badge-out') }}">{{ $status }}</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('inventory.show', $item) }}" class="table-action table-action--primary">View</a>
                            <a href="{{ route('inventory.edit', $item) }}" class="table-action table-action--muted">Edit</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <p class="empty-state__title">No inventory items found</p>
                            <p class="empty-state__text">
                                @if(request()->filled('q') || request()->filled('category') || request()->filled('status'))
                                    Try clearing filters, or add a new item to the catalog.
                                @else
                                    Start the catalog by adding your first BARGO inventory item.
                                @endif
                            </p>
                            <div class="mt-4 flex flex-wrap justify-center gap-2">
                                @if(request()->filled('q') || request()->filled('category') || request()->filled('status'))
                                    <a href="{{ route('inventory.index') }}" class="btn-secondary">Clear filters</a>
                                @endif
                                <a href="{{ route('inventory.create') }}" class="btn-primary">Add Item</a>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($items->hasPages())
    <div class="mt-5">{{ $items->links() }}</div>
@endif
@endsection
