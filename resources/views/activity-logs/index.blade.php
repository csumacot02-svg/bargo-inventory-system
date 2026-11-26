@extends('layouts.app')

@section('title', 'Activity Log')
@section('heading', 'Activity Log')

@section('content')
<p class="mb-4 text-sm text-slsu-navy/70">Who did what in BARGO: sign-in, inventory, stock-in, requests, payments, scans, and user approvals.</p>

<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('activity-logs.index') }}" class="btn-primary">Activity</a>
    <a href="{{ route('logs.stock-movements') }}" class="btn-secondary">Stock ledger</a>
</div>

<form method="GET" class="panel mb-5 flex flex-wrap items-end gap-2 p-4">
    <div>
        <label class="form-label">Search</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-input w-48" placeholder="Description or action">
    </div>
    <div>
        <label class="form-label">Module</label>
        <select name="module" class="form-input w-40">
            <option value="">All</option>
            @foreach ($modules as $module)
                <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Action</label>
        <select name="action" class="form-input w-44">
            <option value="">All</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">User</label>
        <select name="user_id" class="form-input w-44">
            <option value="">All</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="form-input">
    </div>
    <div>
        <label class="form-label">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="form-input">
    </div>
    <button class="btn-secondary" type="submit">Filter</button>
    <a href="{{ route('activity-logs.export', request()->query()) }}" class="btn-primary">Export CSV</a>
</form>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>When</th>
                <th>User</th>
                <th>Action</th>
                <th>Module</th>
                <th>Description</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="whitespace-nowrap">{{ $log->created_at->timezone(config('app.timezone'))->format('M d, Y h:i A') }}</td>
                    <td>{{ $log->user?->name ?? '—' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->module }}</td>
                    <td>{{ $log->description }}</td>
                    <td class="text-xs">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-slsu-navy/60">No activity recorded yet. Sign in, scan an item, or post stock to create log entries.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
