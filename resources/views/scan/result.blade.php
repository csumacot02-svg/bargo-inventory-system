@extends('layouts.app')

@section('title', 'Scan Result')
@section('heading', 'Scan Result')

@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('scan.index') }}" class="btn-secondary">Scan another</a>
    <a href="{{ route('inventory.show', $item) }}" class="btn-primary">Open item record</a>
    <a href="{{ route('inventory.edit', $item) }}" class="btn-secondary">Edit item</a>
</div>

<div class="panel grid gap-4 p-6 sm:grid-cols-2">
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Matched Code</p>
        <p class="mt-1 font-semibold text-slsu-navy">{{ $code }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Stock Status</p>
        @php $status = $item->stockStatus(); @endphp
        <p class="mt-1"><span class="badge {{ $status === 'ok' ? 'badge-ok' : ($status === 'low' ? 'badge-low' : 'badge-out') }}">{{ $status }}</span></p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Item</p>
        <p class="mt-1 text-lg font-semibold">{{ $item->name }} ({{ $item->item_code }})</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Quantity On Hand</p>
        <p class="mt-1 text-lg font-semibold">{{ number_format($item->quantity_on_hand, 2) }} {{ $item->unit }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Category</p>
        <p class="mt-1">{{ $item->category?->name }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Location</p>
        <p class="mt-1">{{ $item->location ?? '—' }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Barcode</p>
        <p class="mt-1">{{ $item->barcode ?? '—' }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">QR Code</p>
        <p class="mt-1">{{ $item->qr_code ?? '—' }}</p>
    </div>
</div>
@endsection
