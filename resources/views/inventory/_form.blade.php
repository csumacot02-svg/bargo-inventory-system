@php($item = $item ?? null)

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label">Item Code</label>
        <input type="text" name="item_code" value="{{ old('item_code', $item->item_code ?? '') }}" class="form-input" placeholder="Auto-generated if blank">
    </div>
    <div>
        <label class="form-label">Item Name</label>
        <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" required class="form-input">
    </div>
    <div>
        <label class="form-label">Category</label>
        <select name="category_id" required class="form-input">
            <option value="">Select category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Supplier</label>
        <select name="supplier_id" class="form-input">
            <option value="">Optional</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $item->supplier_id ?? '') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Unit</label>
        <input type="text" name="unit" value="{{ old('unit', $item->unit ?? 'pcs') }}" required class="form-input">
    </div>
    <div>
        <label class="form-label">Unit Price (₱)</label>
        <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', $item->unit_price ?? 0) }}" class="form-input" placeholder="Used in BARGO consolidated report">
    </div>
    <div>
        <label class="form-label">Quantity On Hand</label>
        @if($item)
            <p class="form-input bg-slsu-sand/50">{{ number_format($item->quantity_on_hand, 2) }} {{ $item->unit }} (ledger-controlled)</p>
            <p class="mt-1 text-xs text-slsu-navy/60">Do not edit quantity here. Use <a class="font-semibold text-slsu-blue underline" href="{{ route('adjustments.create', ['item' => $item->id]) }}">Stock Adjustment</a> for counts, spoilage, or corrections.</p>
        @else
            <input type="number" step="0.01" min="0" name="quantity_on_hand" value="{{ old('quantity_on_hand', 0) }}" required class="form-input">
            <p class="mt-1 text-xs text-slsu-navy/60">Posted as opening balance in the stock ledger.</p>
        @endif
    </div>
    <div>
        <label class="form-label">Reorder Level</label>
        <input type="number" step="0.01" min="0" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level ?? 10) }}" required class="form-input">
    </div>
    <div>
        <label class="form-label">Condition</label>
        <select name="condition" class="form-input">
            @foreach (['good','fair','damaged','expired'] as $condition)
                <option value="{{ $condition }}" @selected(old('condition', $item->condition ?? 'good') === $condition)>{{ ucfirst($condition) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Expiration Date <span class="text-xs font-normal text-slsu-maroon">(required if perishable)</span></label>
        <input type="date" name="expiration_date" value="{{ old('expiration_date', optional($item->expiration_date ?? null)->format('Y-m-d')) }}" class="form-input">
    </div>
    <div>
        <label class="form-label">Shelf Life (days)</label>
        <input type="number" min="1" name="shelf_life_days" value="{{ old('shelf_life_days', $item->shelf_life_days ?? '') }}" class="form-input" placeholder="e.g. 30 for produce">
    </div>
    <div>
        <label class="form-label">Location</label>
        <input type="text" name="location" value="{{ old('location', $item->location ?? 'BARGO Storeroom') }}" class="form-input">
    </div>
    <div>
        <label class="form-label">Barcode</label>
        <input type="text" name="barcode" value="{{ old('barcode', $item->barcode ?? '') }}" class="form-input">
    </div>
    <div>
        <label class="form-label">QR Code</label>
        <input type="text" name="qr_code" value="{{ old('qr_code', $item->qr_code ?? '') }}" class="form-input" placeholder="Auto if blank">
    </div>
</div>

<div class="rounded-lg border border-slsu-maroon/20 bg-slsu-maroon/5 p-4 text-sm text-slsu-navy">
    <label class="flex items-start gap-2 font-medium">
        <input type="checkbox" name="is_perishable" value="1" id="is_perishable" @checked(old('is_perishable', $item->is_perishable ?? false)) class="mt-1 rounded border-slsu-navy/30 text-slsu-navy focus:ring-slsu-blue">
        <span>
            Perishable goods
            <span class="mt-1 block text-xs font-normal text-slsu-navy/70">
                For rice, vegetables, fruits, fertilizer, livestock-related products, etc. Usable stock is the on-hand quantity only while not expired.
                Expired perishables cannot be issued. Always set an expiration date and prefer FIFO (first expiry, first out).
            </span>
        </span>
    </label>
</div>

<div>
    <label class="form-label">Description</label>
    <textarea name="description" rows="3" class="form-input">{{ old('description', $item->description ?? '') }}</textarea>
</div>

<label class="flex items-center gap-2 text-sm font-medium text-slsu-navy">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-slsu-navy/30 text-slsu-navy focus:ring-slsu-blue">
    Active item
</label>
