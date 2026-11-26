@extends('layouts.app')

@section('title', $stockIn->reference_no)
@section('heading', 'Stock-In '.$stockIn->reference_no)

@section('content')
<div class="panel mb-6 grid gap-4 p-6 sm:grid-cols-2">
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Date</p>
        <p class="mt-1 font-semibold">{{ $stockIn->received_date->format('M d, Y') }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Supplier</p>
        <p class="mt-1 font-semibold">{{ $stockIn->supplier?->name ?? '—' }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Received By</p>
        <p class="mt-1 font-semibold">{{ $stockIn->receiver?->name }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Notes</p>
        <p class="mt-1">{{ $stockIn->notes ?: '—' }}</p>
    </div>
    @if($stockIn->isVoided())
        <div class="sm:col-span-2 rounded-lg border border-slsu-maroon/30 bg-slsu-maroon/10 p-3 text-sm text-slsu-maroon">
            Voided {{ $stockIn->voided_at->format('M d, Y h:i A') }} by {{ $stockIn->voidedBy?->name }}. Reason: {{ $stockIn->void_reason }}
        </div>
    @endif
</div>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Lot expiry</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stockIn->items as $line)
                <tr>
                    <td>{{ $line->inventoryItem?->name }}</td>
                    <td>{{ number_format($line->quantity, 2) }}</td>
                    <td>{{ $line->unit_cost !== null ? number_format($line->unit_cost, 2) : '—' }}</td>
                    <td>{{ optional($line->expiration_date)->format('M d, Y') ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4 flex flex-wrap gap-3">
    <a href="{{ route('stock-ins.index') }}" class="btn-secondary">Back</a>
    @if(auth()->user()->canVoidStockIn() && ! $stockIn->isVoided())
        <form method="POST" action="{{ route('stock-ins.void', $stockIn) }}" class="flex flex-wrap items-end gap-2" onsubmit="return confirm('Void this stock-in and reverse remaining quantities?')">
            @csrf
            <input type="text" name="void_reason" required minlength="5" class="form-input max-w-xs" placeholder="Void reason">
            <button class="btn-accent" type="submit">Void stock-in</button>
        </form>
    @endif
</div>
@endsection
