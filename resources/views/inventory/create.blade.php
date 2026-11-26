@extends('layouts.app')

@section('title', 'Add Inventory Item')
@section('heading', 'Add Inventory Item')

@section('content')
<form method="POST" action="{{ route('inventory.store') }}" class="panel mx-auto max-w-3xl space-y-5 p-6">
    @csrf
    @include('inventory._form')
    <div class="flex gap-3">
        <button type="submit" class="btn-primary">Save Item</button>
        <a href="{{ route('inventory.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>
@endsection
