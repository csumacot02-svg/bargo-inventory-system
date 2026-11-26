@extends('layouts.app')

@section('title', 'Edit Inventory Item')
@section('heading', 'Edit Inventory Item')

@section('content')
<form method="POST" action="{{ route('inventory.update', $item) }}" class="panel mx-auto max-w-3xl space-y-5 p-6">
    @csrf
    @method('PUT')
    @include('inventory._form')
    <div class="flex gap-3">
        <button type="submit" class="btn-primary">Update Item</button>
        <a href="{{ route('inventory.show', $item) }}" class="btn-secondary">Cancel</a>
    </div>
</form>
@endsection
