@extends('layouts.app')

@section('title', 'Suppliers')
@section('heading', 'Suppliers')

@section('content')
<form method="POST" action="{{ route('suppliers.store') }}" class="panel mb-6 grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
    @csrf
    <input type="text" name="name" required class="form-input" placeholder="Supplier name">
    <input type="text" name="contact_person" class="form-input" placeholder="Contact person">
    <input type="text" name="phone" class="form-input" placeholder="Phone">
    <input type="email" name="email" class="form-input" placeholder="Email">
    <input type="text" name="address" class="form-input sm:col-span-2" placeholder="Address">
    <button class="btn-primary" type="submit">Add supplier</button>
</form>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Items</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($suppliers as $supplier)
                <tr>
                    <td class="font-medium">{{ $supplier->name }}</td>
                    <td>{{ $supplier->contact_person ?: '—' }} {{ $supplier->phone ? '· '.$supplier->phone : '' }}</td>
                    <td>{{ $supplier->items_count }}</td>
                    <td>
                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm font-semibold text-slsu-maroon" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $suppliers->links() }}</div>
@endsection
