@extends('layouts.guest')

@section('title', $intentLabel.' Sign In')
@section('auth_subtitle', $intentLabel.' access')

@section('auth_content')
<a href="{{ route('gateway') }}" class="mb-5 inline-flex items-center gap-1.5 text-sm font-semibold text-slsu-navy/70 transition hover:text-slsu-blue">
    <span aria-hidden="true">←</span> Back to account type selection
</a>

<h1 class="font-display text-2xl font-semibold text-slsu-navy">{{ $intentLabel }} Sign In</h1>
@if ($intent === 'bargo')
    <p class="mt-1 text-sm text-slsu-navy/70">Use your administrator or staff ID and password.</p>
@else
    <p class="mt-1 text-sm text-slsu-navy/70">Sign in to your {{ $intentLabel }} account</p>
@endif

<form
    method="POST"
    action="{{ route('login.role.store', ['intent' => $intent]) }}"
    class="mt-6 space-y-4"
    onsubmit="this.querySelector('[type=submit]').disabled=true; this.querySelector('[type=submit]').textContent='Signing in…';"
>
    @csrf
    <div>
        <label class="form-label" for="id_number">ID Number</label>
        <input id="id_number" type="text" name="id_number" value="{{ old('id_number') }}" required autofocus autocomplete="username" class="form-input" placeholder="e.g. 2310121-1">
        @error('id_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    @include('auth.partials.password-field', [
        'id' => 'password',
        'label' => 'Password',
        'autocomplete' => 'current-password',
    ])
    <label class="flex items-center gap-2 text-sm text-slsu-navy/80">
        <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-slsu-navy/30 text-slsu-navy focus:ring-slsu-blue">
        Remember Me
    </label>
    <button type="submit" class="btn-primary w-full">Sign In</button>
</form>

@include('auth.partials.social-buttons', ['intent' => $intent])

<div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slsu-navy/10 pt-5 text-sm">
    @if($allowRegistration ?? true)
        <p class="text-slsu-navy/70">
            Don't have an account?
            <a href="{{ route('register.role', ['intent' => $intent]) }}" class="font-semibold text-slsu-blue hover:underline">Register</a>
        </p>
    @else
        <p class="text-slsu-navy/70">Additional administrators are created in User Management.</p>
    @endif
    <a href="{{ route('password.request') }}" class="font-semibold text-slsu-navy/70 hover:underline">Forgot Password?</a>
</div>
@endsection
