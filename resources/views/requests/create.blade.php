@extends('layouts.app')

@section('title', 'New Request')
@section('heading', 'Submit Inventory Request')

@section('content')
<form method="POST" action="{{ route('requests.store') }}" class="panel space-y-5 p-6" id="request-form">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <p class="text-xs font-semibold tracking-wide text-slsu-navy/50 uppercase">Client</p>
        </div>
        <div>
            <label class="form-label">Client name</label>
            <input type="text" name="client_name" value="{{ old('client_name') }}" required class="form-input" placeholder="Full name on Payment Order">
            @error('client_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Client email</label>
            <input type="email" name="client_email" value="{{ old('client_email') }}" required class="form-input" placeholder="for due & paid notices">
            @error('client_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Client ID No. <span class="font-normal text-slsu-navy/50">(optional)</span></label>
            <input type="text" name="client_id_number" value="{{ old('client_id_number') }}" class="form-input" placeholder="ID / student / employee no.">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Office / Department <span class="font-normal text-slsu-navy/50">(optional)</span></label>
            <input type="text" name="client_office" value="{{ old('client_office') }}" class="form-input" placeholder="e.g. College of Education">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Purpose</label>
            <input type="text" name="purpose" value="{{ old('purpose') }}" required class="form-input" placeholder="e.g. Office use / Campus event">
        </div>
        <div>
            <label class="form-label">Payment Type</label>
            <select name="payment_type" id="payment_type" class="form-input">
                <option value="">N/A</option>
                <option value="cash" @selected(old('payment_type') === 'cash')>Cash</option>
                <option value="credit" @selected(old('payment_type') === 'credit')>Credit</option>
            </select>
        </div>
        <div id="credit-plan" class="sm:col-span-2 grid gap-4 sm:grid-cols-2 {{ old('payment_type') === 'credit' ? '' : 'hidden' }}">
            <div>
                <label class="form-label">Credit plan</label>
                <select name="installment_frequency" id="installment_frequency" class="form-input">
                    <option value="once" @selected(old('installment_frequency', 'once') === 'once')>Single payment (one due date)</option>
                    <option value="daily" @selected(old('installment_frequency') === 'daily')>Daily installments</option>
                    <option value="monthly" @selected(old('installment_frequency') === 'monthly')>Monthly installments</option>
                </select>
            </div>
            <div id="installment-count-wrap" class="{{ in_array(old('installment_frequency'), ['daily', 'monthly'], true) ? '' : 'hidden' }}">
                <label class="form-label">Number of installments</label>
                <input type="number" name="installment_count" id="installment_count" min="2" max="90" value="{{ old('installment_count', 3) }}" class="form-input">
                <p class="mt-1 text-xs text-slsu-navy/55">Daily: 2–90 · Monthly: 2–36</p>
                @error('installment_count')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div id="due-date-wrap" class="{{ old('installment_frequency', 'once') === 'once' || old('payment_type') !== 'credit' ? '' : 'hidden' }}">
                <label class="form-label">Due date</label>
                <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-input">
                @error('due_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div id="start-date-wrap" class="{{ in_array(old('installment_frequency'), ['daily', 'monthly'], true) ? '' : 'hidden' }}">
                <label class="form-label">First installment date</label>
                <input type="date" name="installment_start_date" value="{{ old('installment_start_date', now()->toDateString()) }}" class="form-input">
                @error('installment_start_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Remarks</label>
            <textarea name="remarks" rows="2" class="form-input">{{ old('remarks') }}</textarea>
        </div>
    </div>

    <div>
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-display text-lg font-semibold text-slsu-navy">Requested Items</h3>
            <button type="button" class="btn-secondary" onclick="addRequestRow()">Add line</button>
        </div>
        <div id="request-rows" class="space-y-3">
            <div class="grid gap-3 rounded-lg border border-slsu-navy/10 bg-slsu-sand/30 p-3 sm:grid-cols-[2fr_1fr]">
                <select name="items[0][inventory_item_id]" required class="form-input">
                    <option value="">Select item</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} — avail {{ number_format($item->quantity_on_hand, 2) }} {{ $item->unit }} · ₱{{ number_format($item->unit_price, 2) }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" min="0.01" name="items[0][quantity_requested]" placeholder="Qty" required class="form-input">
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn-primary">Submit Request</button>
        <a href="{{ route('requests.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>

<script>
    let reqIndex = 1;
    const reqOptions = `@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }} — avail {{ number_format($item->quantity_on_hand, 2) }} {{ $item->unit }} · ₱{{ number_format($item->unit_price, 2) }}</option>@endforeach`;

    function addRequestRow() {
        const wrap = document.getElementById('request-rows');
        const div = document.createElement('div');
        div.className = 'grid gap-3 rounded-lg border border-slsu-navy/10 bg-slsu-sand/30 p-3 sm:grid-cols-[2fr_1fr]';
        div.innerHTML = `
            <select name="items[${reqIndex}][inventory_item_id]" required class="form-input">
                <option value="">Select item</option>
                ${reqOptions}
            </select>
            <input type="number" step="0.01" min="0.01" name="items[${reqIndex}][quantity_requested]" placeholder="Qty" required class="form-input">
        `;
        wrap.appendChild(div);
        reqIndex++;
    }

    function syncCreditFields() {
        const type = document.getElementById('payment_type').value;
        const freq = document.getElementById('installment_frequency').value;
        const creditPlan = document.getElementById('credit-plan');
        const countWrap = document.getElementById('installment-count-wrap');
        const dueWrap = document.getElementById('due-date-wrap');
        const startWrap = document.getElementById('start-date-wrap');
        const countInput = document.getElementById('installment_count');

        creditPlan.classList.toggle('hidden', type !== 'credit');

        const multi = type === 'credit' && (freq === 'daily' || freq === 'monthly');
        countWrap.classList.toggle('hidden', !multi);
        startWrap.classList.toggle('hidden', !multi);
        dueWrap.classList.toggle('hidden', !(type === 'credit' && freq === 'once'));

        if (freq === 'monthly') {
            countInput.max = 36;
            if (Number(countInput.value) > 36) countInput.value = 36;
        } else {
            countInput.max = 90;
        }
    }

    document.getElementById('payment_type').addEventListener('change', syncCreditFields);
    document.getElementById('installment_frequency').addEventListener('change', syncCreditFields);
    syncCreditFields();
</script>
@endsection
