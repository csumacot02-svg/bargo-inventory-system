@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', $dashboardTitle ?? 'Dashboard')
@section('subheading', $dashboardDate ?? now()->format('F j, l'))

@section('content')
@php
    $initials = function (?string $name): string {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $letters = collect($parts)->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');

        return $letters !== '' ? $letters : 'BG';
    };
@endphp

@if(auth()->user()->canManageInventory() && $lowStock > 0)
    <div class="alert-banner alert-banner--warn">
        <p>
            <strong>{{ $lowStock }}</strong> item{{ $lowStock > 1 ? 's are' : ' is' }} at or below reorder level.
        </p>
        <a href="{{ route('alerts.index') }}" class="shrink-0 font-semibold text-amber-900 underline-offset-2 hover:underline">Open alerts</a>
    </div>
@endif

<div class="dash-layout">
    <div class="dash-main">
        <div class="dash-kpi-grid">
            <a href="{{ route('inventory.index') }}" class="dash-kpi">
                <div>
                    <p class="dash-kpi__label">Inventory</p>
                    <p class="dash-kpi__value">{{ number_format($totalItems) }}</p>
                    <p class="dash-kpi__hint">Active catalog items</p>
                </div>
                <span class="dash-kpi__arrow" aria-hidden="true">→</span>
            </a>
            <a href="{{ route('requests.pending') }}" class="dash-kpi">
                <div>
                    <p class="dash-kpi__label">Pending</p>
                    <p class="dash-kpi__value">{{ number_format($pendingRequests) }}</p>
                    <p class="dash-kpi__hint">Awaiting approval</p>
                </div>
                <span class="dash-kpi__arrow" aria-hidden="true">→</span>
            </a>
            <a href="{{ route('alerts.index') }}" class="dash-kpi {{ $lowStock > 0 ? 'dash-kpi--warn' : '' }}">
                <div>
                    <p class="dash-kpi__label">Low stock</p>
                    <p class="dash-kpi__value">{{ number_format($lowStock) }}</p>
                    <p class="dash-kpi__hint">At or below reorder</p>
                </div>
                <span class="dash-kpi__arrow" aria-hidden="true">→</span>
            </a>
            <a href="{{ auth()->user()->canAccessReports() ? route('reports.index') : route('requests.completed') }}" class="dash-kpi dash-kpi--accent">
                <div>
                    <p class="dash-kpi__label">Sales {{ now()->year }}</p>
                    <p class="dash-kpi__value dash-kpi__value--money">₱{{ number_format($productSales, 0) }}</p>
                    <p class="dash-kpi__hint">₱{{ number_format($productSalesMonth, 0) }} this month</p>
                </div>
                <span class="dash-kpi__arrow" aria-hidden="true">→</span>
            </a>
        </div>

        <div class="dash-chart-row">
            <section class="dash-card">
                <div class="dash-card__head">
                    <div>
                        <h2 class="dash-card__title">Request activity</h2>
                        <p class="dash-card__meta">New requests · last 6 months</p>
                    </div>
                    <a href="{{ route('requests.index') }}" class="section-link">View all</a>
                </div>
                <div class="dash-chart-frame">
                    <canvas id="dash-chart-requests" aria-label="Request activity chart"></canvas>
                </div>
            </section>

            <section class="dash-card">
                <div class="dash-card__head">
                    <div>
                        <h2 class="dash-card__title">Sales & stock-ins</h2>
                        <p class="dash-card__meta">Released sales vs receiving volume</p>
                    </div>
                    <span class="dash-pill">Last 6 months</span>
                </div>
                <div class="dash-chart-frame">
                    <canvas id="dash-chart-sales" aria-label="Sales and stock-ins chart"></canvas>
                </div>
            </section>
        </div>

        <div class="dash-widget-row">
            <section class="dash-card">
                <div class="dash-card__head">
                    <h2 class="dash-card__title">Recent requests</h2>
                    <a href="{{ route('requests.index') }}" class="section-link">Open</a>
                </div>
                <div class="dash-feed">
                    @forelse ($recentRequests as $req)
                        <a href="{{ route('requests.show', $req) }}" class="dash-feed__item">
                            <span class="dash-avatar">{{ $initials($req->clientDisplayName()) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="dash-feed__top">
                                    <span class="dash-feed__name">{{ $req->clientDisplayName() }}</span>
                                    <span class="dash-feed__time">{{ $req->created_at->diffForHumans(short: true) }}</span>
                                </span>
                                <span class="dash-feed__text">{{ $req->request_no }} · {{ $req->itemsSummary(2) }}</span>
                            </span>
                            <span class="badge {{ $req->statusBadgeClass() }} shrink-0">{{ $req->statusLabel() }}</span>
                        </a>
                    @empty
                        <div class="empty-state py-8">
                            <p class="empty-state__title">No requests yet</p>
                            <p class="empty-state__text">New inventory requests will show up here.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="dash-card">
                <div class="dash-card__head">
                    <div>
                        <h2 class="dash-card__title">Inventory mix</h2>
                        <p class="dash-card__meta">{{ number_format($categoryTotal) }} items by category</p>
                    </div>
                </div>
                <div class="dash-donut-wrap">
                    <div class="dash-donut-frame">
                        <canvas id="dash-chart-categories" aria-label="Inventory by category"></canvas>
                        <div class="dash-donut-center">
                            <p class="dash-donut-center__value">{{ number_format($categoryTotal) }}</p>
                            <p class="dash-donut-center__label">Items</p>
                        </div>
                    </div>
                    <ul class="dash-legend">
                        @forelse ($categoryBreakdown as $row)
                            @php $pct = $categoryTotal > 0 ? round(($row['count'] / $categoryTotal) * 100, 1) : 0; @endphp
                            <li>
                                <span class="dash-legend__swatch" data-legend-index="{{ $loop->index }}"></span>
                                <span class="min-w-0 flex-1 truncate">{{ $row['label'] }}</span>
                                <span class="tabular-nums text-slsu-navy/60">{{ $pct }}%</span>
                                <span class="tabular-nums font-semibold">{{ number_format($row['count']) }}</span>
                            </li>
                        @empty
                            <li class="text-slsu-navy/50">No categorized items yet.</li>
                        @endforelse
                    </ul>
                </div>
            </section>

            <section class="dash-card">
                <div class="dash-card__head">
                    <h2 class="dash-card__title">Stock attention</h2>
                    @if(auth()->user()->canManageInventory())
                        <a href="{{ route('alerts.index') }}" class="section-link">Alerts</a>
                    @endif
                </div>
                <div class="dash-activity">
                    @forelse ($lowStockItems as $item)
                        <a href="{{ route('inventory.show', $item) }}" class="dash-activity__item">
                            <span class="dash-activity__icon dash-activity__icon--warn" aria-hidden="true">!</span>
                            <span class="min-w-0 flex-1">
                                <span class="dash-activity__title">{{ $item->name }}</span>
                                <span class="dash-activity__meta">{{ $item->category?->name ?? 'Uncategorized' }} · reorder {{ number_format($item->reorder_level, 0) }}</span>
                            </span>
                            <span class="dash-activity__time">{{ number_format($item->quantity_on_hand, 0) }} {{ $item->unit }}</span>
                        </a>
                    @empty
                        <div class="empty-state py-8">
                            <p class="empty-state__title">Stock looks healthy</p>
                            <p class="empty-state__text">No items are at or below reorder right now.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="dash-card">
            <div class="dash-card__head">
                <div>
                    <h2 class="dash-card__title">Inventory board</h2>
                    <p class="dash-card__meta">Recently added catalog items</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="dash-pill">Latest</span>
                    @if(auth()->user()->canManageInventory())
                        <a href="{{ route('inventory.create') }}" class="dash-add-btn" title="Add item">+</a>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="dash-board-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>On hand</th>
                            <th>Status</th>
                            <th class="text-right">Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentItems as $item)
                            @php $status = $item->stockStatus(); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('inventory.show', $item) }}" class="inventory-name hover:text-slsu-blue">{{ $item->name }}</a>
                                    <p class="inventory-code">{{ $item->item_code }}</p>
                                </td>
                                <td>{{ $item->category?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ number_format($item->quantity_on_hand, 2) }} <span class="text-slsu-navy/45">{{ $item->unit }}</span></td>
                                <td>
                                    <span class="badge {{ $status === 'ok' ? 'badge-ok' : ($status === 'low' ? 'badge-low' : 'badge-out') }}">{{ $status }}</span>
                                </td>
                                <td class="text-right text-slsu-navy/55">{{ $item->created_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <p class="empty-state__title">No inventory records yet</p>
                                        <p class="empty-state__text">Add your first item to start tracking stock.</p>
                                        @if(auth()->user()->canManageInventory())
                                            <a href="{{ route('inventory.create') }}" class="btn-primary mt-4">Add Item</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="dash-stat-strip">
            <div>
                <p class="dash-stat-strip__label">Approved</p>
                <p class="dash-stat-strip__value">{{ number_format($approvedRequests) }}</p>
            </div>
            <div>
                <p class="dash-stat-strip__label">Released</p>
                <p class="dash-stat-strip__value">{{ number_format($releasedRequests) }}</p>
            </div>
            <div>
                <p class="dash-stat-strip__label">Completed</p>
                <p class="dash-stat-strip__value">{{ number_format($completedRequests) }}</p>
            </div>
            <div>
                <p class="dash-stat-strip__label">Stock-ins (month)</p>
                <p class="dash-stat-strip__value">{{ number_format($stockInsThisMonth) }}</p>
            </div>
            <div>
                <p class="dash-stat-strip__label">Inventory value</p>
                <p class="dash-stat-strip__value">₱{{ number_format($inventoryValue, 0) }}</p>
            </div>
            <div>
                <p class="dash-stat-strip__label">All requests</p>
                <p class="dash-stat-strip__value">{{ number_format($totalRequests) }}</p>
            </div>
        </div>
    </div>

    <aside class="dash-rail">
        <section class="dash-card dash-card--rail">
            <div class="dash-card__head">
                <h2 class="dash-card__title">Calendar</h2>
                <div class="dash-cal-nav">
                    <button type="button" class="dash-cal-nav__btn" data-cal-prev aria-label="Previous month">‹</button>
                    <button type="button" class="dash-cal-nav__btn" data-cal-next aria-label="Next month">›</button>
                </div>
            </div>
            <div id="dash-calendar" class="dash-calendar" data-due-dates='@json($chartPayload['dueDates'])'></div>
        </section>

        <section class="dash-card dash-card--rail">
            <div class="dash-card__head">
                <h2 class="dash-card__title">Payment schedule</h2>
                <a href="{{ route('requests.released') }}" class="section-link">Due soon</a>
            </div>
            <div class="dash-schedule">
                @forelse ($upcomingDues as $due)
                    @php
                        $req = $due->inventoryRequest;
                        $tone = $due->isOverdue() ? 'overdue' : ($due->due_date?->isToday() ? 'today' : 'upcoming');
                    @endphp
                    <a href="{{ $req ? route('requests.show', $req) : '#' }}" class="dash-schedule__item dash-schedule__item--{{ $tone }}">
                        <div class="dash-schedule__when">
                            <p class="dash-schedule__date">{{ $due->due_date?->format('M d') }}</p>
                            <p class="dash-schedule__dow">{{ $due->due_date?->format('D') }}</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="dash-schedule__title">{{ $req?->clientDisplayName() ?? 'Client' }}</p>
                            <p class="dash-schedule__meta">{{ $req?->request_no }} · Inst. #{{ $due->sequence }}</p>
                        </div>
                        <p class="dash-schedule__amt">₱{{ number_format((float) $due->balanceDue(), 0) }}</p>
                    </a>
                @empty
                    <div class="empty-state py-6">
                        <p class="empty-state__title">No dues this window</p>
                        <p class="empty-state__text">Upcoming installment dates will appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="dash-card dash-card--rail">
            <div class="dash-card__head">
                <h2 class="dash-card__title">Recent activity</h2>
                @if(auth()->user()->canViewAuditLogs())
                    <a href="{{ route('activity-logs.index') }}" class="section-link">Log</a>
                @endif
            </div>
            <div class="dash-activity">
                @forelse ($recentActivities as $log)
                    <div class="dash-activity__item">
                        <span class="dash-activity__icon dash-activity__icon--info" aria-hidden="true">{{ $initials($log->user?->name) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="dash-activity__title">{{ $log->description ?: ucfirst(str_replace('_', ' ', (string) $log->action)) }}</span>
                            <span class="dash-activity__meta">{{ $log->user?->name ?? 'System' }} · {{ $log->module }}</span>
                        </span>
                        <span class="dash-activity__time">{{ $log->created_at?->diffForHumans(short: true) }}</span>
                    </div>
                @empty
                    <div class="empty-state py-6">
                        <p class="empty-state__title">Quiet so far</p>
                        <p class="empty-state__text">Operational events will show here.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </aside>
</div>

<script type="application/json" id="dash-chart-data">@json($chartPayload)</script>
@endsection
