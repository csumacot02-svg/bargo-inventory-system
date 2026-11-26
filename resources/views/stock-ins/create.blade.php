@extends('layouts.app')

@section('title', 'Record Stock-In')
@section('heading', 'Record Stock-In')

@section('content')
<form method="POST" action="{{ route('stock-ins.store') }}" class="panel space-y-5 p-6" id="stock-in-form">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="form-label">Supplier</label>
            <select name="supplier_id" class="form-input">
                <option value="">Optional</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Received Date</label>
            <input type="date" name="received_date" value="{{ old('received_date', now()->toDateString()) }}" required class="form-input">
        </div>
    </div>
    <div>
        <label class="form-label">Notes</label>
        <textarea name="notes" rows="2" class="form-input">{{ old('notes') }}</textarea>
    </div>

    <div>
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-display text-lg font-semibold text-slsu-navy">Delivered Items</h3>
            <button type="button" class="btn-secondary" onclick="addStockRow()">Add line</button>
        </div>
        <div id="stock-rows" class="space-y-3">
            <div class="grid gap-3 rounded-lg border border-slsu-navy/10 bg-slsu-sand/30 p-3 sm:grid-cols-[2fr_1fr_1fr_1fr]">
                <select name="items[0][inventory_item_id]" required class="form-input">
                    <option value="">Select item</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->item_code }})</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" min="0.01" name="items[0][quantity]" placeholder="Qty" required class="form-input">
                <input type="number" step="0.01" min="0" name="items[0][unit_cost]" placeholder="Unit cost" class="form-input">
                <input type="date" name="items[0][expiration_date]" class="form-input" title="Lot expiry (perishables)">
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn-primary">Save Stock-In</button>
        <a href="{{ route('stock-ins.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>

<script>
    let rowIndex = 1;
    const itemOptions = `@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }} ({{ $item->item_code }})</option>@endforeach`;

    function addStockRow() {
        const wrap = document.getElementById('stock-rows');
        const div = document.createElement('div');
        div.className = 'grid gap-3 rounded-lg border border-slsu-navy/10 bg-slsu-sand/30 p-3 sm:grid-cols-[2fr_1fr_1fr_1fr]';
        div.innerHTML = `
            <select name="items[${rowIndex}][inventory_item_id]" required class="form-input">
                <option value="">Select item</option>
                ${itemOptions}
            </select>
            <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][quantity]" placeholder="Qty" required class="form-input">
            <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_cost]" placeholder="Unit cost" class="form-input">
            <input type="date" name="items[${rowIndex}][expiration_date]" class="form-input" title="Lot expiry">
        `;
        wrap.appendChild(div);
        rowIndex++;
    }
</script>
@endsection
