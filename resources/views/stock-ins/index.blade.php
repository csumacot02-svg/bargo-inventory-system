@extends('layouts.app')

@section('title', 'Stock-In')
@section('heading', 'Stock-In Process')

@section('content')
<div class="mb-5 flex justify-end">
    <a href="{{ route('stock-ins.create') }}" class="btn-primary">Record Stock-In</a>
</div>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Date</th>
                <th>Supplier</th>
                <th>Received By</th>
                <th>Lines</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stockIns as $stockIn)
                <tr>
                    <td class="font-medium">{{ $stockIn->reference_no }}</td>
                    <td>{{ $stockIn->received_date->format('M d, Y') }}</td>
                    <td>{{ $stockIn->supplier?->name ?? '—' }}</td>
                    <td>{{ $stockIn->receiver?->name }}</td>
                    <td>{{ $stockIn->items->count() }}</td>
                    <td><a href="{{ route('stock-ins.show', $stockIn) }}" class="text-sm font-semibold text-slsu-blue hover:underline">View</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-10 text-center text-slsu-navy/60">No stock-in records yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $stockIns->links() }}</div>
@endsection
