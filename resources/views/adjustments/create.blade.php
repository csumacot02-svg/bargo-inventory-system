@extends('layouts.app')

@section('title', 'Stock Adjustment')
@section('heading', 'Stock Adjustment / Physical Count')

@section('content')
<p class="mb-4 text-sm text-slsu-navy/70">Post counts, spoilage, damage, or corrections to the stock ledger. Quantity on the item form cannot be edited after create.</p>

<form method="POST" action="{{ route('adjustments.store') }}" class="panel space-y-5 p-6">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="form-label">Item</label>
            <select name="inventory_item_id" required class="form-input">
                <option value="">Select item</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}" @selected(old('inventory_item_id', $selectedId) == $item->id)>
                        {{ $item->name }} ({{ $item->item_code }}) — on hand {{ number_format($item->quantity_on_hand, 2) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Type</label>
            <select name="mode" required class="form-input">
                <option value="count" @selected(old('mode') === 'count')>Physical count (enter the counted on-hand qty)</option>
                <option value="spoilage" @selected(old('mode') === 'spoilage')>Spoilage / dispose (qty to remove)</option>
                <option value="damage" @selected(old('mode') === 'damage')>Damage write-off (qty to remove)</option>
                <option value="correction" @selected(old('mode') === 'correction')>Correction (+/− change)</option>
            </select>
        </div>
        <div>
            <label class="form-label">Quantity</label>
            <input type="number" step="0.01" name="quantity" value="{{ old('quantity') }}" required class="form-input">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Reason</label>
            <textarea name="reason" rows="3" required minlength="5" class="form-input" placeholder="Required for audit">{{ old('reason') }}</textarea>
        </div>
    </div>
    <div class="flex gap-3">
        <button class="btn-primary" type="submit">Post to ledger</button>
        <a href="{{ route('inventory.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>
@endsection
