@extends('layouts.app')

@section('title', 'QR / Barcode Scan')
@section('heading', 'QR & Barcode Scanning')

@section('content')
<div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
    <section class="panel space-y-4 p-6">
        <h2 class="font-display text-xl font-semibold text-slsu-navy">Scan or enter code</h2>
        <p class="text-sm text-slsu-navy/70">Match against item QR code, barcode, or item code for faster identification and stock checks.</p>

        <form method="POST" action="{{ route('scan.lookup') }}" class="flex flex-col gap-3 sm:flex-row" id="manual-scan-form">
            @csrf
            <input type="text" name="code" id="code-input" value="{{ old('code') }}" required autofocus
                   class="form-input" placeholder="Scan barcode / paste QR value / item code">
            <button type="submit" class="btn-primary">Look Up</button>
        </form>

        <div>
            <div class="mb-2 flex items-center justify-between">
                <p class="text-sm font-semibold text-slsu-navy">Camera scanner</p>
                <button type="button" id="toggle-camera" class="btn-secondary">Start Camera</button>
            </div>
            <div id="reader" class="overflow-hidden rounded-xl border border-slsu-navy/15 bg-black/90"></div>
            <p id="scan-status" class="mt-2 text-xs text-slsu-navy/60">Allow camera access to scan QR codes or barcodes.</p>
        </div>
    </section>

    <section class="panel p-6">
        <h2 class="font-display text-xl font-semibold text-slsu-navy">How to use</h2>
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slsu-navy/80">
            <li>Use a USB barcode scanner into the input field, or start the camera for QR codes.</li>
            <li>The system looks up <strong>QR code</strong>, <strong>barcode</strong>, and <strong>item code</strong>.</li>
            <li>Open the item record to review lots and post a <strong>Stock Adjustment</strong> if the count is wrong.</li>
        </ol>
        <div class="mt-6 rounded-lg border border-slsu-blue/20 bg-slsu-blue/10 p-4 text-sm text-slsu-navy">
            Tip: When adding inventory, fill in <em>Barcode</em> / <em>QR Code</em> so scanning works during stock-in and issuance.
        </div>
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/scan.js')
@endpush
