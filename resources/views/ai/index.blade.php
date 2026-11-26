@extends('layouts.app')

@section('title', 'Inventory Insights')
@section('heading', 'Inventory Insights')

@php
    $hasAnalysis = is_array($analysis) && ! empty($analysis['summary'] ?? $analysis['analyzed_at'] ?? null);
    $source = $analysis['source'] ?? null;
    $isCached = ! empty($analysis['cached']);
    $sourceLabel = match (true) {
        $source === 'deepseek' && $isCached => 'AI · Cached',
        $source === 'deepseek' => 'AI analysis',
        $source === 'rule_based' => 'Rule-based',
        default => null,
    };
@endphp

@section('content')
<div class="panel mb-6 overflow-hidden">
    <div class="flex flex-col gap-4 border-b border-slsu-navy/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm text-slsu-navy/75">
                Low stock, stock-out patterns, and a 30-day usage outlook for BARGO decision support.
            </p>
            @if($hasAnalysis && ! empty($analysis['analyzed_at']))
                <p class="mt-1 text-xs text-slsu-navy/55">
                    Updated {{ \Illuminate\Support\Carbon::parse($analysis['analyzed_at'])->timezone(config('app.timezone'))->format('M d, Y g:i A') }}
                    @if($sourceLabel)
                        · <span class="font-semibold text-slsu-navy/70">{{ $sourceLabel }}</span>
                    @endif
                </p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('ai.analyze') }}">
                @csrf
                <button type="submit" class="btn-primary">Run analysis</button>
            </form>
            <form method="POST" action="{{ route('ai.analyze') }}" onsubmit="return confirm('Generate a fresh analysis now?');">
                @csrf
                <input type="hidden" name="force" value="1">
                <button type="submit" class="btn-secondary" title="Refresh even if a recent result is available">Refresh</button>
            </form>
        </div>
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="stat-tile">
        <p class="stat-tile__label">Active items</p>
        <p class="stat-tile__value">{{ $snapshot['totals']['active_items'] }}</p>
    </div>
    <div class="stat-tile {{ ($snapshot['totals']['low_stock'] ?? 0) > 0 ? 'stat-tile--warn' : '' }}">
        <p class="stat-tile__label">Low stock</p>
        <p class="stat-tile__value {{ ($snapshot['totals']['low_stock'] ?? 0) > 0 ? 'text-amber-700' : '' }}">{{ $snapshot['totals']['low_stock'] }}</p>
    </div>
    <div class="stat-tile">
        <p class="stat-tile__label">Perishable SKUs</p>
        <p class="stat-tile__value">{{ $snapshot['totals']['perishable_items'] }}</p>
    </div>
    <div class="stat-tile {{ ($snapshot['totals']['expiring_or_expired'] ?? 0) > 0 ? 'stat-tile--warn' : '' }}">
        <p class="stat-tile__label">Expiring / expired</p>
        <p class="stat-tile__value {{ ($snapshot['totals']['expiring_or_expired'] ?? 0) > 0 ? 'text-amber-700' : '' }}">{{ $snapshot['totals']['expiring_or_expired'] }}</p>
    </div>
</div>

<script type="application/json" id="ai-chart-data">@json($charts)</script>

<div class="mb-6 grid gap-5 xl:grid-cols-3">
    <section class="panel p-5">
        <h2 class="font-display text-base font-semibold text-slsu-navy">Low stock</h2>
        <p class="mt-0.5 text-xs text-slsu-navy/55">On hand vs reorder level</p>
        <div class="chart-frame mt-4">
            <canvas id="chart-low-stock" aria-label="Low-stock quantity bar chart"></canvas>
        </div>
        <p data-chart-empty="low_stock" class="mt-6 hidden text-center text-sm text-slsu-navy/55">No low-stock items.</p>
    </section>

    <section class="panel p-5">
        <h2 class="font-display text-base font-semibold text-slsu-navy">Frequent stock-outs</h2>
        <p class="mt-0.5 text-xs text-slsu-navy/55">{{ $snapshot['frequent_stockouts']['value_label'] ?? 'Quantity issued (90 days)' }}</p>
        <div class="chart-frame mt-4">
            <canvas id="chart-stockouts" aria-label="Frequent stock-out bar chart"></canvas>
        </div>
        <p data-chart-empty="stockouts" class="mt-6 hidden text-center text-sm text-slsu-navy/55">No issuance history in the last 90 days.</p>
    </section>

    <section class="panel p-5">
        <h2 class="font-display text-base font-semibold text-slsu-navy">30-day forecast</h2>
        <p class="mt-0.5 text-xs text-slsu-navy/55">
            @if(($charts['forecast']['source'] ?? 'rule_based') === 'deepseek')
                Predicted outbound quantity
            @else
                Based on recent average usage
            @endif
        </p>
        <div class="chart-frame mt-4">
            <canvas id="chart-forecast" aria-label="30-day forecast bar chart"></canvas>
        </div>
        <p data-chart-empty="forecast" class="mt-6 hidden text-center text-sm text-slsu-navy/55">Not enough history to forecast yet.</p>
    </section>
</div>

@if($hasAnalysis)
    <section class="panel mb-6 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slsu-navy/10 px-5 py-4">
            <h2 class="font-display text-lg font-semibold text-slsu-navy">Recommendations</h2>
            @if($sourceLabel)
                <span class="badge badge-pending">{{ $sourceLabel }}</span>
            @endif
        </div>
        <div class="space-y-4 px-5 py-5">
            @if(! empty($analysis['summary']))
                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slsu-navy/85">{{ $analysis['summary'] }}</p>
            @endif
            @if(! empty($analysis['recommendations']))
                <ul class="list-disc space-y-1.5 pl-5 text-sm text-slsu-navy/80">
                    @foreach ($analysis['recommendations'] as $rec)
                        <li>{{ $rec }}</li>
                    @endforeach
                </ul>
            @endif
            @if(! empty($analysis['predictions']))
                <div class="overflow-x-auto rounded-lg border border-slsu-navy/10">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>30-day forecast</th>
                                <th>Stock-out risk</th>
                                <th>Days of cover</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($analysis['predictions'] as $row)
                                <tr>
                                    <td class="font-medium">{{ $row['name'] ?? '—' }}</td>
                                    <td>{{ $row['forecast_30d_qty'] ?? 0 }}</td>
                                    <td class="capitalize">{{ $row['stockout_risk'] ?? '—' }}</td>
                                    <td>{{ $row['days_until_stockout'] ?? '—' }}</td>
                                    <td class="text-slsu-navy/70">{{ $row['note'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endif

<section class="panel overflow-hidden">
    <div class="border-b border-slsu-navy/10 px-5 py-4">
        <h2 class="font-display text-lg font-semibold text-slsu-navy">Recent stock movements</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Change</th>
                    <th>After</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($snapshot['recent_movements'] as $move)
                    <tr>
                        <td>{{ $move['at'] }}</td>
                        <td class="font-medium">{{ $move['item'] }}</td>
                        <td class="capitalize">{{ str_replace('_', ' ', $move['type']) }}</td>
                        <td>{{ $move['change'] }}</td>
                        <td>{{ $move['after'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slsu-navy/60">No movements yet. Complete a stock-in or release to build trend data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
