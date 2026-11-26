@extends('layouts.app')

@section('title', $heading)
@section('heading', $heading)

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slsu-navy/65">
        @if($filter === 'history')
            Finalized requests (completed, rejected, cancelled). Records are preserved.
        @elseif($filter === 'completed')
            Transactions marked finished after release. Read-only.
        @elseif($filter === 'released')
            Items released — mark as completed when the client has received everything.
        @else
            Active stock-out / request workflow.
        @endif
    </p>
    @if(auth()->user()->canSubmitRequests() && ! in_array($filter, ['completed', 'history'], true))
        <a href="{{ route('requests.create') }}" class="btn-primary">New Request</a>
    @endif
</div>

<form method="GET" action="{{ route('requests.index') }}" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
    <input type="hidden" name="filter" value="{{ $filter }}">
    <div class="lg:col-span-2">
        <label class="form-label" for="q">Search</label>
        <input id="q" type="text" name="q" value="{{ $search }}" class="form-input" placeholder="Request no., client, requester…">
    </div>
    @if($filter === 'history')
        <div>
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-input">
                <option value="">All history</option>
                <option value="completed" @selected($statusFilter === 'completed')>Completed</option>
                <option value="rejected" @selected($statusFilter === 'rejected')>Rejected</option>
                <option value="cancelled" @selected($statusFilter === 'cancelled')>Cancelled</option>
            </select>
        </div>
    @endif
    <div>
        <label class="form-label" for="from">From</label>
        <input id="from" type="date" name="from" value="{{ $from }}" class="form-input">
    </div>
    <div class="flex gap-2">
        <div class="min-w-0 flex-1">
            <label class="form-label" for="to">To</label>
            <input id="to" type="date" name="to" value="{{ $to }}" class="form-input">
        </div>
        <button type="submit" class="btn-primary shrink-0 self-end">Filter</button>
    </div>
</form>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Request No.</th>
                <th>Client</th>
                <th>Recorded by</th>
                <th>Purpose</th>
                @if(in_array($filter, ['completed', 'history', 'released'], true))
                    <th>Items</th>
                @endif
                <th>Requested</th>
                @if(in_array($filter, ['released', 'completed', 'history'], true))
                    <th>Released</th>
                @endif
                @if(in_array($filter, ['completed', 'history'], true))
                    <th>Completed</th>
                @endif
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $req)
                <tr>
                    <td class="font-medium">{{ $req->request_no }}</td>
                    <td>{{ $req->clientDisplayName() }}</td>
                    <td>{{ $req->requester?->name }}</td>
                    <td>{{ $req->purpose }}</td>
                    @if(in_array($filter, ['completed', 'history', 'released'], true))
                        <td class="max-w-[12rem] truncate text-sm" title="{{ $req->itemsSummary(10) }}">{{ $req->itemsSummary() }}</td>
                    @endif
                    <td>{{ $req->created_at->format('M d, Y') }}</td>
                    @if(in_array($filter, ['released', 'completed', 'history'], true))
                        <td>{{ optional($req->released_at)->format('M d, Y') ?? '—' }}</td>
                    @endif
                    @if(in_array($filter, ['completed', 'history'], true))
                        <td>{{ optional($req->completed_at)->format('M d, Y') ?? '—' }}</td>
                    @endif
                    <td><span class="badge {{ $req->statusBadgeClass() }}">{{ $req->statusLabel() }}</span></td>
                    <td class="whitespace-nowrap space-x-2">
                        <a href="{{ route('requests.show', $req) }}" class="text-sm font-semibold text-slsu-blue hover:underline">Open</a>
                        @if(in_array($req->status, ['released', 'completed'], true))
                            <a href="{{ route('requests.release-slip', $req) }}" class="text-sm font-semibold text-slsu-navy hover:underline" target="_blank">Print</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="py-10 text-center text-slsu-navy/60">No requests found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $requests->links() }}</div>
@endsection
