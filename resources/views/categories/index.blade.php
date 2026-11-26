@extends('layouts.app')

@section('title', 'Categories')
@section('heading', 'Categories')

@section('content')
<form method="POST" action="{{ route('categories.store') }}" class="panel mb-6 grid gap-3 p-5 sm:grid-cols-[2fr_2fr_auto]">
    @csrf
    <input type="text" name="name" required class="form-input" placeholder="Category name">
    <input type="text" name="description" class="form-input" placeholder="Description (optional)">
    <button class="btn-primary" type="submit">Add</button>
</form>

<div class="table-shell overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Items</th>
                <th>Description</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>
                        <form method="POST" action="{{ route('categories.update', $category) }}" class="flex flex-wrap gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $category->name }}" required class="form-input max-w-xs">
                            <input type="text" name="description" value="{{ $category->description }}" class="form-input max-w-sm">
                            <button class="btn-secondary" type="submit">Save</button>
                        </form>
                    </td>
                    <td>{{ $category->items_count }}</td>
                    <td>{{ $category->description ?: '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
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
<div class="mt-4">{{ $categories->links() }}</div>
@endsection
