@extends('layouts.app')

@section('title', 'Reports')
@section('heading', 'Inventory Reports')

@php
    $reportTitle = match ($type) {
        'low' => 'Low Stock Report',
        'stock-in' => 'Stock-In History',
        'requests' => 'Request / Transaction History',
        default => 'Inventory Summary',
    };
    $from = request('from');
    $to = request('to');
@endphp

@section('content')
<form method="GET" class="no-print panel mb-6 flex flex-wrap items-end gap-3 p-5">
    <div>
        <label class="form-label">Report Type</label>
        <select name="type" class="form-input">
            <option value="inventory" @selected($type === 'inventory')>Inventory Summary</option>
            <option value="low" @selected($type === 'low')>Low Stock</option>
            <option value="stock-in" @selected($type === 'stock-in')>Stock-In History</option>
            <option value="requests" @selected($type === 'requests')>Request / Transaction History</option>
        </select>
    </div>
    <div>
        <label class="form-label">From</label>
        <input type="date" name="from" value="{{ $from }}" class="form-input">
    </div>
    <div>
        <label class="form-label">To</label>
        <input type="date" name="to" value="{{ $to }}" class="form-input">
    </div>
    <button type="submit" class="btn-primary">Generate</button>
    <a href="{{ route('reports.export', request()->query()) }}" class="btn-secondary">Export CSV</a>
    <button type="button" class="btn-secondary" onclick="window.print()">Print</button>
    <a href="{{ route('reports.consolidated-bargo', ['year' => now()->year]) }}" class="btn-primary">Consolidated BARGO Products Report</a>
</form>

<div class="no-print mb-6 rounded-lg border border-slsu-navy/15 bg-white p-4 text-sm text-slsu-navy/80">
    <strong class="text-slsu-navy">Printable official format:</strong>
    Open the <a class="font-semibold text-slsu-blue underline" href="{{ route('reports.consolidated-bargo') }}">Consolidated BARGO Products Report</a>
    (monthly sales by actual inventory items, yearly totals, highest-selling rank, and prepared/approved signatories).
</div>

<div class="print-only print-letterhead">
    <img src="{{ asset('images/slsu-logo.png') }}" alt="SLSU Logo">
    <div>
        <p class="print-letterhead__campus">Southern Leyte State University — Hinunangan Campus</p>
        <p class="print-letterhead__office">Business Affairs and Resource Generating Office</p>
        <h1>{{ $reportTitle }}</h1>
        <p class="print-letterhead__meta">
            @if($from || $to)
                Period: {{ $from ?: '—' }} to {{ $to ?: '—' }} ·
            @endif
            Printed {{ now()->timezone(config('app.timezone'))->format('F d, Y h:i A') }}
            · Inventory value ₱{{ number_format($inventoryValue, 2) }}
            · Product sales ₱{{ number_format($productSales, 2) }}
        </p>
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2 print-totals">
    <div class="stat-tile">
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Total Inventory Value</p>
        <p class="font-display mt-2 text-3xl font-semibold text-slsu-navy">₱{{ number_format($inventoryValue, 2) }}</p>
        <p class="mt-1 text-xs text-slsu-navy/55">Current stock × unit price</p>
    </div>
    <div class="stat-tile">
        <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Product Sales</p>
        <p class="font-display mt-2 text-3xl font-semibold text-slsu-navy">₱{{ number_format($productSales, 2) }}</p>
        <p class="mt-1 text-xs text-slsu-navy/55">Released requests{{ $from || $to ? ' in selected period' : ' this year' }}</p>
    </div>
</div>

@if($type === 'inventory')
    <div class="table-shell overflow-x-auto print-sheet">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Value</th>
                    <th>Reorder</th>
                    <th>Condition</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($inventory as $item)
                    <tr>
                        <td>{{ $item->item_code }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->category?->name }}</td>
                        <td>{{ number_format($item->quantity_on_hand, 2) }} {{ $item->unit }}</td>
                        <td>₱{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td>₱{{ number_format($item->onHandValue(), 2) }}</td>
                        <td>{{ number_format($item->reorder_level, 2) }}</td>
                        <td class="capitalize">{{ $item->condition }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="font-semibold">Total inventory value</td>
                    <td class="font-semibold">₱{{ number_format($inventoryValue, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
@elseif($type === 'low')
    <div class="table-shell overflow-x-auto print-sheet">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Reorder Level</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lowStock as $item)
                    <tr>
                        <td>{{ $item->item_code }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ number_format($item->quantity_on_hand, 2) }}</td>
                        <td>{{ number_format($item->reorder_level, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center">No low-stock items.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@elseif($type === 'stock-in')
    <div class="table-shell overflow-x-auto print-sheet">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Lines</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stockIns as $stockIn)
                    <tr>
                        <td>{{ $stockIn->reference_no }}</td>
                        <td>{{ $stockIn->received_date->format('M d, Y') }}</td>
                        <td>{{ $stockIn->supplier?->name ?? '—' }}</td>
                        <td>{{ $stockIn->items->count() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="table-shell overflow-x-auto print-sheet">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Request No.</th>
                    <th>Requested by</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $req)
                    <tr>
                        <td>{{ $req->request_no }}</td>
                        <td>{{ $req->requester?->name }}</td>
                        <td class="capitalize">{{ $req->status }}</td>
                        <td class="capitalize">{{ $req->payment_type ?? '—' }}</td>
                        <td>{{ $req->created_at->format('M d, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="print-only print-signs">
    <div>
        <div class="print-signs__label">Prepared by:</div>
        <div class="print-signs__name">{{ $signatories['prepared_by_name'] }}</div>
        <div class="print-signs__role">{{ $signatories['prepared_by_title'] }}</div>
    </div>
    <div>
        <div class="print-signs__label">Approved by:</div>
        <div class="print-signs__name">{{ $signatories['approved_by_name'] }}</div>
        <div class="print-signs__role">{{ $signatories['approved_by_title'] }}</div>
    </div>
</div>
@endsection
