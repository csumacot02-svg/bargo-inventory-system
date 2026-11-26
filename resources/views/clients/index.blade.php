@extends('layouts.app')

@section('title', 'Clients')
@section('heading', 'Client Releases')

@section('content')
<form method="GET" action="{{ route('clients.index') }}" class="panel mb-5 grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
    <div class="lg:col-span-2">
        <label class="form-label" for="q">Search</label>
        <input id="q" type="text" name="q" value="{{ $search }}" class="form-input" placeholder="Client, ID, office, request no.">
    </div>
    <div>
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="form-input">
            <option value="released" @selected($status === 'released')>Released</option>
            <option value="approved" @selected($status === 'approved')>Approved (awaiting release)</option>
            <option value="pending" @selected($status === 'pending')>Pending</option>
            <option value="all" @selected($status === 'all')>All</option>
        </select>
    </div>
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

<div class="mb-5 grid gap-4 lg:grid-cols-3">
    <div class="panel p-5 lg:col-span-1">
        <p class="text-xs font-semibold tracking-wide text-slsu-navy/50 uppercase">Clients in filter</p>
        <p class="mt-1 font-display text-3xl font-semibold text-slsu-navy">{{ $clientSummary->count() }}</p>
        <p class="mt-1 text-sm text-slsu-navy/60">Unique names</p>
    </div>
    <div class="panel overflow-hidden lg:col-span-2">
        <div class="border-b border-slsu-navy/10 px-5 py-3">
            <p class="text-xs font-semibold tracking-wide text-slsu-navy/50 uppercase">Client roster</p>
        </div>
        <div class="max-h-56 overflow-y-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>ID</th>
                        <th>Releases</th>
                        <th>Last activity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientSummary as $row)
                        <tr>
                            <td class="font-medium">
                                {{ $row->client_label }}
                                @if($row->client_office)
                                    <span class="block text-xs font-normal text-slsu-navy/55">{{ $row->client_office }}</span>
                                @endif
                            </td>
                            <td>{{ $row->client_id_number ?: '—' }}</td>
                            <td>{{ $row->request_count }}</td>
                            <td>{{ $row->last_activity_at ? $row->last_activity_at->format('M d, Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slsu-navy/60">No clients found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Request</th>
                <th>Purpose</th>
                <th>Amount</th>
                <th>OR</th>
                <th>Status</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($releases as $req)
                <tr>
                    <td>
                        <span class="font-medium">{{ $req->clientDisplayName() }}</span>
                        @if($req->client_id_number)
                            <span class="block text-xs text-slsu-navy/55">ID {{ $req->client_id_number }}</span>
                        @endif
                        @if($req->client_office)
                            <span class="block text-xs text-slsu-navy/55">{{ $req->client_office }}</span>
                        @endif
                    </td>
                    <td class="font-medium">{{ $req->request_no }}</td>
                    <td>{{ $req->purpose }}</td>
                    <td>₱{{ number_format($req->totalAmount(), 2) }}</td>
                    <td>{{ $req->or_number ?: '—' }}</td>
                    <td><span class="badge badge-pending">{{ $req->status }}</span></td>
                    <td>
                        @if($req->status === 'released' && $req->released_at)
                            {{ $req->released_at->format('M d, Y') }}
                        @else
                            {{ $req->created_at->format('M d, Y') }}
                        @endif
                    </td>
                    <td class="space-x-2 whitespace-nowrap">
                        <a href="{{ route('requests.show', $req) }}" class="text-sm font-semibold text-slsu-blue hover:underline">Open</a>
                        @if($req->status === 'released')
                            <a href="{{ route('requests.release-slip', $req) }}" class="text-sm font-semibold text-slsu-navy hover:underline" target="_blank">Payment Order</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-10 text-center text-slsu-navy/60">No client release records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $releases->links() }}</div>
@endsection
