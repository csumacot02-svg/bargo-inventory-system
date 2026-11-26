@extends('layouts.base')

@section('body')
<div class="app-shell" data-app-shell>
    <div class="app-sidebar-backdrop no-print" data-sidebar-backdrop hidden></div>

    <aside class="app-sidebar no-print" data-app-sidebar id="app-sidebar">
        <div class="absolute inset-0 opacity-20" style="background-image: url('{{ asset('images/slsu-campus.png') }}'); background-size: cover; background-position: center;"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-slsu-navy/95 via-slsu-navy/92 to-slsu-navy-deep"></div>

        <div class="app-sidebar__inner">
            <div class="app-sidebar__brand">
                <img src="{{ asset('images/slsu-logo.png') }}" alt="SLSU Logo" class="app-sidebar__logo">
                <div class="app-sidebar__brand-text">
                    <p class="font-display text-lg leading-tight font-semibold">SLSU Hinunangan</p>
                    <p class="text-xs tracking-wide text-slsu-sky uppercase">BARGO Inventory</p>
                </div>
                <button
                    type="button"
                    class="sidebar-toggle sidebar-toggle--collapse"
                    data-sidebar-toggle
                    aria-controls="app-sidebar"
                    aria-expanded="true"
                    aria-label="Collapse navigation"
                    title="Collapse navigation"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <nav class="app-sidebar__nav" aria-label="Primary">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard', 'admin.dashboard', 'staff.dashboard') ? 'nav-link-active' : '' }}" title="Dashboard">
                    <x-nav-icon name="dashboard" />
                    <span class="nav-link__text">Dashboard</span>
                </a>
                @if(auth()->user()->canManageInventory())
                    <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'nav-link-active' : '' }}" title="Inventory">
                        <x-nav-icon name="inventory" />
                        <span class="nav-link__text">Inventory</span>
                    </a>
                    <a href="{{ route('adjustments.create') }}" class="nav-link {{ request()->routeIs('adjustments.*') ? 'nav-link-active' : '' }}" title="Adjustments">
                        <x-nav-icon name="adjustments" />
                        <span class="nav-link__text">Adjustments</span>
                    </a>
                    <a href="{{ route('stock-ins.index') }}" class="nav-link {{ request()->routeIs('stock-ins.*') ? 'nav-link-active' : '' }}" title="Stock-In">
                        <x-nav-icon name="stock-in" />
                        <span class="nav-link__text">Stock-In</span>
                    </a>
                    <a href="{{ route('scan.index') }}" class="nav-link {{ request()->routeIs('scan.*') ? 'nav-link-active' : '' }}" title="QR / Barcode Scan">
                        <x-nav-icon name="scan" />
                        <span class="nav-link__text">QR / Barcode Scan</span>
                    </a>
                    <a href="{{ route('alerts.index') }}" class="nav-link {{ request()->routeIs('alerts.*') ? 'nav-link-active' : '' }}" title="Stock Alerts">
                        <x-nav-icon name="alerts" />
                        <span class="nav-link__text flex w-full items-center justify-between gap-2">
                            <span>Stock Alerts</span>
                            @if(($navAlertCount ?? 0) > 0)
                                <span class="rounded-md bg-slsu-maroon px-2 py-0.5 text-[11px] font-bold text-white">{{ $navAlertCount }}</span>
                            @endif
                        </span>
                        @if(($navAlertCount ?? 0) > 0)
                            <span class="nav-link__badge">{{ $navAlertCount > 9 ? '9+' : $navAlertCount }}</span>
                        @endif
                    </a>
                @endif
                @php
                    $requestsSectionOpen = request()->routeIs('requests.*');
                    $requestsFilter = request()->routeIs('requests.index')
                        ? request('filter', 'all')
                        : request('filter');
                @endphp
                <div
                    class="nav-accordion {{ $requestsSectionOpen ? 'is-open' : '' }}"
                    data-nav-accordion
                    data-nav-section="requests"
                >
                    <button
                        type="button"
                        class="nav-link nav-accordion__trigger {{ $requestsSectionOpen ? 'nav-link-active' : '' }}"
                        aria-expanded="{{ $requestsSectionOpen ? 'true' : 'false' }}"
                        aria-controls="nav-requests-submenu"
                        data-nav-accordion-trigger
                        title="Requests"
                    >
                        <x-nav-icon name="requests" />
                        <span class="nav-link__text">Requests</span>
                        <svg class="nav-accordion__chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div id="nav-requests-submenu" class="nav-accordion__panel" data-nav-accordion-panel>
                        <div class="nav-sub">
                            <a href="{{ route('requests.index', ['filter' => 'all']) }}" class="nav-sublink {{ $requestsFilter === 'all' ? 'nav-sublink-active' : '' }}">All Requests</a>
                            <a href="{{ route('requests.pending') }}" class="nav-sublink {{ $requestsFilter === 'pending' ? 'nav-sublink-active' : '' }}">Pending</a>
                            <a href="{{ route('requests.approved') }}" class="nav-sublink {{ $requestsFilter === 'approved' ? 'nav-sublink-active' : '' }}">Approved</a>
                            <a href="{{ route('requests.released') }}" class="nav-sublink {{ $requestsFilter === 'released' ? 'nav-sublink-active' : '' }}">Released</a>
                            <a href="{{ route('requests.completed') }}" class="nav-sublink {{ $requestsFilter === 'completed' ? 'nav-sublink-active' : '' }}">Completed</a>
                            <a href="{{ route('requests.history') }}" class="nav-sublink {{ $requestsFilter === 'history' ? 'nav-sublink-active' : '' }}">Request History</a>
                        </div>
                    </div>
                </div>
                @if(auth()->user()->canManageInventory())
                    <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'nav-link-active' : '' }}" title="Clients">
                        <x-nav-icon name="clients" />
                        <span class="nav-link__text">Clients</span>
                    </a>
                @endif
                @if(auth()->user()->canAccessReports())
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}" title="Reports">
                        <x-nav-icon name="reports" />
                        <span class="nav-link__text">Reports</span>
                    </a>
                    <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai.*') ? 'nav-link-active' : '' }}" title="Inventory Insights">
                        <x-nav-icon name="insights" />
                        <span class="nav-link__text">Inventory Insights</span>
                    </a>
                @endif
                @if(auth()->user()->canViewAuditLogs())
                    <a href="{{ route('activity-logs.index') }}" class="nav-link {{ request()->routeIs('activity-logs.*') ? 'nav-link-active' : '' }}" title="Activity Log">
                        <x-nav-icon name="activity" />
                        <span class="nav-link__text">Activity Log</span>
                    </a>
                    <a href="{{ route('logs.stock-movements') }}" class="nav-link {{ request()->routeIs('logs.stock-movements*') ? 'nav-link-active' : '' }}" title="Stock Ledger">
                        <x-nav-icon name="ledger" />
                        <span class="nav-link__text">Stock Ledger</span>
                    </a>
                @endif
                @if(auth()->user()->canManageMasterData())
                    <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'nav-link-active' : '' }}" title="Categories">
                        <x-nav-icon name="categories" />
                        <span class="nav-link__text">Categories</span>
                    </a>
                    <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'nav-link-active' : '' }}" title="Suppliers">
                        <x-nav-icon name="suppliers" />
                        <span class="nav-link__text">Suppliers</span>
                    </a>
                @endif
                @if(auth()->user()->canManageDocumentTemplates())
                    <a href="{{ route('document-templates.index') }}" class="nav-link {{ request()->routeIs('document-templates.*') ? 'nav-link-active' : '' }}" title="OOP Template">
                        <x-nav-icon name="template" />
                        <span class="nav-link__text">OOP Template</span>
                    </a>
                @endif
                @if(auth()->user()->canManageUsers())
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}" title="User Management">
                        <x-nav-icon name="users" />
                        <span class="nav-link__text flex w-full items-center justify-between gap-2">
                            <span>User Management</span>
                            @if(($navPendingUsers ?? 0) > 0)
                                <span class="rounded-md bg-amber-500 px-2 py-0.5 text-[11px] font-bold text-white">{{ $navPendingUsers }}</span>
                            @endif
                        </span>
                        @if(($navPendingUsers ?? 0) > 0)
                            <span class="nav-link__badge">{{ $navPendingUsers > 9 ? '9+' : $navPendingUsers }}</span>
                        @endif
                    </a>
                @endif
            </nav>

            <div class="app-sidebar__account">
                <a href="{{ route('profile.show') }}" class="app-sidebar__profile group" title="{{ auth()->user()->name }}">
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="app-sidebar__avatar">
                    <div class="app-sidebar__profile-text min-w-0 w-full">
                        <p class="truncate text-sm font-semibold group-hover:underline">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slsu-sky">{{ auth()->user()->roleLabel() }}</p>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="app-sidebar__logout mt-3">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-white/80 underline-offset-2 hover:text-white hover:underline">Sign out</button>
                </form>
                <p class="app-sidebar__tagline mt-4 text-[11px] leading-relaxed text-white/50">Knowledge. Innovation. Service</p>
            </div>
        </div>
    </aside>

    <div class="app-main">
        <header class="no-print border-b border-slsu-navy/10 bg-white/80 backdrop-blur">
            <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <div class="flex min-w-0 items-start gap-3">
                    <button
                        type="button"
                        class="sidebar-toggle sidebar-toggle--header"
                        data-sidebar-toggle
                        aria-controls="app-sidebar"
                        aria-expanded="true"
                        aria-label="Toggle navigation"
                        title="Toggle navigation"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-[0.18em] text-slsu-maroon uppercase">Business Affairs & Resource Generating Office</p>
                        <h1 class="font-display text-2xl font-semibold text-slsu-navy">@yield('heading', 'Dashboard')</h1>
                        @hasSection('subheading')
                            <p class="mt-0.5 text-sm text-slsu-navy/55">@yield('subheading')</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if(auth()->user()->canManageInventory() && ($navAlertCount ?? 0) > 0)
                        <a href="{{ route('alerts.index') }}" class="hidden rounded-lg border border-slsu-maroon/30 bg-slsu-maroon/10 px-3 py-2 text-xs font-semibold text-slsu-maroon sm:inline-flex">
                            {{ $navAlertCount }} alert{{ $navAlertCount > 1 ? 's' : '' }}
                        </a>
                    @endif
                    <img src="{{ asset('images/slsu-logo.png') }}" alt="" class="hidden h-12 w-12 sm:block">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slsu-navy/15 bg-white px-3 py-2 text-xs font-semibold text-slsu-navy hover:bg-slsu-navy/5">Sign out</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-6 sm:px-6">
            @if (session('success'))
                <div class="no-print mb-4 rounded-lg border border-slsu-green/30 bg-slsu-green/10 px-4 py-3 text-sm text-slsu-green">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="no-print mb-4 rounded-lg border border-slsu-maroon/30 bg-slsu-maroon/10 px-4 py-3 text-sm text-slsu-maroon">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="no-print mb-4 rounded-lg border border-slsu-maroon/30 bg-slsu-maroon/10 px-4 py-3 text-sm text-slsu-maroon">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="no-print border-t border-slsu-navy/10 px-4 py-4 text-xs text-slsu-navy/60 sm:px-6">
            Southern Leyte State University — Hinunangan Campus · BARGO Inventory Management System
        </footer>
    </div>
</div>
@endsection
