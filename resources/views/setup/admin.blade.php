@extends('layouts.guest')

@section('title', $isRecovery ? 'Restore Administrator Access' : 'First Administrator Setup')
@section('auth_subtitle', $isRecovery ? 'Emergency recovery' : 'Secure first-time setup')

@section('auth_content')
@if($isRecovery)
    <h1 class="font-display text-2xl font-semibold text-slsu-navy">Restore Administrator Access</h1>
    <p class="mt-1 text-sm text-slsu-navy/70">
        No <strong>active</strong> administrator can sign in. Create a new admin account below, or ask IT to reactivate an existing one with:
        <code class="mt-1 block rounded bg-slsu-navy/5 px-2 py-1 text-[11px]">php artisan bargo:restore-admin email@example.com</code>
    </p>
    @if($inactiveAdmins->isNotEmpty())
        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900">
            <p class="font-semibold">Inactive administrator accounts still on file:</p>
            <ul class="mt-1 list-disc pl-4">
                @foreach ($inactiveAdmins as $inactive)
                    <li>{{ $inactive->name }} ({{ $inactive->email }}) — {{ $inactive->statusLabel() }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@else
    <h1 class="font-display text-2xl font-semibold text-slsu-navy">Create First Administrator</h1>
    <p class="mt-1 text-sm text-slsu-navy/70">
        No administrator exists yet. This protected setup creates the only initial Admin account.
        Role is fixed to <strong>Administrator</strong> — it cannot be chosen on this form.
    </p>
@endif

<form method="POST" action="{{ route('setup.admin.store') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label class="form-label" for="name">Full Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="form-input">
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="form-label" for="id_number">ID Number</label>
        <input id="id_number" name="id_number" type="text" value="{{ old('id_number') }}" required class="form-input" placeholder="e.g. 2310121-1">
        <p class="mt-1 text-xs text-slsu-navy/55">You will use this to sign in instead of email.</p>
        @error('id_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="form-label" for="email">Email Address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="form-input">
        @if($isRecovery)
            <p class="mt-1 text-xs text-slsu-navy/55">Use a <strong>new</strong> email (not one already registered).</p>
        @endif
        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    @include('auth.partials.password-field', [
        'id' => 'password',
        'label' => 'Password',
        'autocomplete' => 'new-password',
    ])
    @include('auth.partials.password-field', [
        'id' => 'password_confirmation',
        'label' => 'Confirm Password',
        'autocomplete' => 'new-password',
    ])
    <button type="submit" class="btn-primary w-full">{{ $isRecovery ? 'Create Recovery Administrator' : 'Create Administrator' }}</button>
</form>

<p class="mt-6 text-center text-sm">
    <a href="{{ route('gateway') }}" class="font-semibold text-slsu-navy/70 hover:text-slsu-blue hover:underline">← Back to account type selection</a>
</p>
@endsection
