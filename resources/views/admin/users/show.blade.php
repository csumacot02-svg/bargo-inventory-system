@extends('layouts.app')

@section('title', $user->name)
@section('heading', 'User Details')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.users.index') }}" class="btn-secondary">Back to users</a>
</div>

<div class="panel mb-6 p-6">
    <div class="mb-5 flex items-center gap-4">
        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full ring-2 ring-slsu-navy/20">
            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}"
                 class="h-full w-full object-cover"
                 style="width:80px;height:80px;max-width:80px;max-height:80px;">
        </div>
        <div>
            <p class="text-xl font-semibold text-slsu-navy">{{ $user->name }}</p>
            <p class="text-sm text-slsu-navy/60">{{ $user->roleLabel() }} · <span class="font-medium {{ $user->isActive() ? 'text-green-600' : 'text-red-500' }}">{{ $user->statusLabel() }}</span></p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">ID Number</p>
            <p class="mt-1">{{ $user->id_number ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Email</p>
            <p class="mt-1 break-all">{{ $user->email }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Employee / Student ID</p>
            <p class="mt-1">{{ $user->employee_id ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Department / Office</p>
            <p class="mt-1">{{ $user->department ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Auth provider</p>
            <p class="mt-1">{{ $user->provider ? ucfirst($user->provider) : 'ID / password' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Email verified</p>
            <p class="mt-1">{{ $user->hasVerifiedEmail() ? optional($user->email_verified_at)->format('M d, Y h:i A') : 'Not verified' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-wider text-slsu-maroon uppercase">Registered</p>
            <p class="mt-1">{{ $user->created_at->format('M d, Y h:i A') }}</p>
        </div>
    </div>
</div>

@if (! $user->id_number)
<div class="panel mb-6 border-amber-300 bg-amber-50 p-5">
    <p class="mb-3 text-sm font-semibold text-amber-800">⚠ No ID number assigned — this user cannot sign in yet.</p>
    <form method="POST" action="{{ route('admin.users.assign-id-number', $user) }}" class="flex items-end gap-3">
        @csrf @method('PATCH')
        <div class="flex-1">
            <label class="form-label text-amber-900" for="id_number_assign">Assign ID Number</label>
            <input id="id_number_assign" name="id_number" type="text" required class="form-input" placeholder="e.g. 2310121-1">
        </div>
        <button type="submit" class="btn-primary shrink-0">Save</button>
    </form>
</div>
@endif

@unless((int) $user->id === (int) auth()->id())
<div class="panel mb-6 p-5">
    <h2 class="font-display text-lg font-semibold text-slsu-navy">Reset password</h2>
    <p class="mt-1 text-sm text-slsu-navy/65">
        Use this when {{ $user->isStaff() ? 'staff' : 'this user' }} forgot their password.
        Confirm with your own administrator password, then share the new password securely.
    </p>
    <form method="POST" action="{{ route('admin.users.update-password', $user) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
        @csrf
        @method('PATCH')
        <div>
            <label class="form-label" for="new_password">New password</label>
            <input id="new_password" type="password" name="password" required autocomplete="new-password" class="form-input">
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label" for="new_password_confirmation">Confirm new password</label>
            <input id="new_password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-input">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label" for="admin_password">Your administrator password</label>
            <input id="admin_password" type="password" name="admin_password" required autocomplete="current-password" class="form-input max-w-md">
            @error('admin_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-primary" onclick="return confirm('Reset password for {{ $user->name }}?');">
                Set new password
            </button>
        </div>
    </form>
</div>
@endunless

<div class="panel mb-6 p-5">
    <h2 class="font-display text-lg font-semibold text-slsu-navy">Update email</h2>
    <p class="mt-1 text-sm text-slsu-navy/65">
        Use this when the current email is inactive or no longer used. Sign-in still uses ID number + password.
        By default the user must verify the new email with a 6-digit code.
    </p>
    <form method="POST" action="{{ route('admin.users.update-email', $user) }}" class="mt-4 space-y-4">
        @csrf
        @method('PATCH')
        <div>
            <label class="form-label" for="email_update">New email address</label>
            <input
                id="email_update"
                type="email"
                name="email"
                value="{{ old('email', $user->email) }}"
                required
                class="form-input"
                placeholder="name@gmail.com"
            >
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-start gap-2 text-sm text-slsu-navy/80">
            <input type="checkbox" name="mark_verified" value="1" class="mt-1" @checked(old('mark_verified'))>
            <span>
                Mark as verified now
                <span class="block text-xs text-slsu-navy/55">Only if you confirmed the new email in person. Skips OTP for this change.</span>
            </span>
        </label>
        <button type="submit" class="btn-primary" onclick="return confirm('Update email for {{ $user->name }}?');">
            Save new email
        </button>
    </form>
</div>
@php
    $isSelf = (int) $user->id === (int) auth()->id();
    $canDeactivate = $user->isActive() && ! $isSelf && ! $user->isLastActiveAdmin();
    $canDemote = $user->isAdmin() && ! $isSelf && ! $user->isLastActiveAdmin();
@endphp

<div class="panel space-y-4 p-6">
    <h2 class="font-display text-xl font-semibold text-slsu-navy">Account actions</h2>
    <div class="flex flex-wrap gap-2">
        @if($user->status === 'pending')
            <form method="POST" action="{{ route('admin.users.approve', $user) }}">@csrf<button class="btn-success">Approve</button></form>
            <form method="POST" action="{{ route('admin.users.reject', $user) }}">@csrf<button class="btn-accent">Reject</button></form>
        @endif
        @if($canDemote)
            <form method="POST" action="{{ route('admin.users.demote', $user) }}" onsubmit="return confirm('Demote {{ $user->name }} from administrator to staff? They will lose admin privileges.');">
                @csrf
                <button class="btn-secondary" type="submit">Demote to staff</button>
            </form>
        @endif
        @if($canDeactivate)
            <form method="POST" action="{{ route('admin.users.suspend', $user) }}" onsubmit="return confirm('Remove access for {{ $user->name }}? They will not be able to sign in. Stock and request history stays in the system.');">
                @csrf
                <button class="btn-accent" type="submit">{{ $user->isAdmin() ? 'Deactivate (left or fired)' : 'Suspend' }}</button>
            </form>
        @endif
        @if(in_array($user->status, ['suspended', 'rejected'], true) && ! $isSelf)
            <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">@csrf<button class="btn-success">Reactivate</button></form>
        @endif
    </div>

    @if($isSelf)
        <p class="text-xs text-slsu-navy/60 border-t border-slsu-navy/10 pt-4">
            You cannot demote or deactivate your own account. Ask another administrator to remove your access if you are leaving.
        </p>
    @elseif($user->isLastActiveAdmin())
        <p class="text-xs text-slsu-navy/60 border-t border-slsu-navy/10 pt-4">
            This is the last active administrator. Create another admin in User Management before demoting or deactivating this account.
            If no active admin can sign in, open <code class="text-[11px]">/setup/admin</code> or run <code class="text-[11px]">php artisan bargo:restore-admin</code>.
        </p>
    @elseif($user->isAdmin())
        <p class="text-xs text-slsu-navy/60 border-t border-slsu-navy/10 pt-4">
            Typical offboarding: <strong>Demote to staff</strong> (optional), then <strong>Deactivate</strong> so they cannot sign in.
            History is kept. If they only need staff duties, demote without deactivating.
        </p>
    @else
        <p class="text-xs text-slsu-navy/60 border-t border-slsu-navy/10 pt-4">
            Staff accounts handle inventory operations and inventory requests. New administrators are created under User Management → Create staff or admin.
        </p>
    @endif
</div>
@endsection
