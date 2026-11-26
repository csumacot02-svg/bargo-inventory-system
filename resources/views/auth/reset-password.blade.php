@extends('layouts.guest')

@section('title', 'Reset Password')
@section('auth_subtitle', 'Choose a new password')

@section('auth_content')
<h1 class="font-display text-2xl font-semibold text-slsu-navy">Reset password</h1>

<form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label class="form-label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required class="form-input">
    </div>
    @include('auth.partials.password-field', [
        'id' => 'password',
        'label' => 'New Password',
        'autocomplete' => 'new-password',
    ])
    @include('auth.partials.password-field', [
        'id' => 'password_confirmation',
        'label' => 'Confirm Password',
        'autocomplete' => 'new-password',
    ])
    <button type="submit" class="btn-primary w-full">Reset password</button>
</form>
@endsection
