@extends('layouts.app')

@section('title', 'My Profile')
@section('heading', 'Personal Information')

@section('content')
<div class="max-w-xl">
    <div class="panel p-6">
        <div class="mb-6 flex items-center gap-4">
            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full ring-2 ring-slsu-navy/20">
                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}"
                     class="h-full w-full object-cover"
                     style="width:80px;height:80px;max-width:80px;max-height:80px;">
            </div>
            <div class="min-w-0">
                <h2 class="truncate text-2xl font-semibold text-slsu-navy">{{ $user->name }}</h2>
                <p class="mt-1 text-sm text-slsu-navy/60">{{ $user->roleLabel() }}</p>
                @if ($user->id_number)
                    <p class="mt-0.5 font-mono text-sm text-slsu-navy/80">ID: {{ $user->id_number }}</p>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Email</p>
                <p class="mt-1 break-all text-sm">{{ $user->email }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Account Status</p>
                <p class="mt-1 text-sm font-medium {{ $user->isActive() ? 'text-green-600' : 'text-red-500' }}">{{ $user->statusLabel() }}</p>
            </div>
            @if ($user->employee_id)
            <div>
                <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Employee ID</p>
                <p class="mt-1 text-sm">{{ $user->employee_id }}</p>
            </div>
            @endif
            @if ($user->department)
            <div>
                <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Department</p>
                <p class="mt-1 text-sm">{{ $user->department }}</p>
            </div>
            @endif
            <div>
                <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Member Since</p>
                <p class="mt-1 text-sm">{{ $user->created_at->format('F d, Y') }}</p>
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('profile.edit') }}" class="btn-primary">Edit Profile</a>
        </div>
    </div>
</div>
@endsection
