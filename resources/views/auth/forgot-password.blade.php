@extends('layouts.guest')

@section('title', 'Forgot Password')
@section('auth_subtitle', 'Password recovery')

@section('auth_content')
<h1 class="font-display text-2xl font-semibold text-slsu-navy">Forgot password</h1>
<p class="mt-1 text-sm text-slsu-navy/70">Enter your email and we will send a reset link.</p>

<form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label class="form-label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-input">
    </div>
    <button type="submit" class="btn-primary w-full">Send reset link</button>
</form>

<p class="mt-6 text-sm">
    <a href="{{ route('gateway') }}" class="font-semibold text-slsu-blue hover:underline">Back to account type selection</a>
</p>
@endsection
