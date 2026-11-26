@extends('layouts.guest')

@section('title', 'Verify Email')
@section('auth_subtitle', 'Email verification required')

@section('auth_content')
<h1 class="font-display text-2xl font-semibold text-slsu-navy">Enter your verification code</h1>
<p class="mt-2 text-sm text-slsu-navy/70">
    We sent a <strong>6-digit code</strong> to <strong>{{ auth()->user()->email }}</strong>.
    Include any leading zeros (example: <code>051921</code>). The code expires in 10 minutes.
    Verifying your email confirms ownership — it does <strong>not</strong> approve your account.
</p>

<form method="POST" action="{{ route('verification.verify') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label class="form-label" for="otp">6-digit code</label>
        <input
            id="otp"
            name="otp"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            pattern="[0-9]{6}"
            maxlength="6"
            value="{{ old('otp') }}"
            required
            autofocus
            class="form-input text-center font-mono text-2xl tracking-[0.4em]"
            placeholder="000000"
        >
        @error('otp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="btn-primary w-full">Verify code</button>
</form>

<form method="POST" action="{{ route('verification.send') }}" class="mt-3">
    @csrf
    <button type="submit" class="btn-secondary w-full">Resend code</button>
</form>

<form method="POST" action="{{ route('logout') }}" class="mt-3">
    @csrf
    <button type="submit" class="w-full text-sm font-semibold text-slsu-navy/70 hover:underline">Sign out</button>
</form>

<p class="mt-4 text-xs text-slsu-navy/60">
    Local development: with <code>MAIL_MAILER=log</code>, the code is written to <code>storage/logs/laravel.log</code>.
</p>
@endsection
