@extends('layouts.guest')

@section('title', 'Pending Approval')
@section('auth_subtitle', 'Awaiting administrator approval')

@section('auth_content')
<h1 class="font-display text-2xl font-semibold text-slsu-navy">Account pending approval</h1>
<p class="mt-2 text-sm text-slsu-navy/70">
    Hello {{ $user->name }}. Your email
    @if($user->hasVerifiedEmail())
        is verified
    @else
        still needs verification
    @endif
    and your account status is <strong>pending</strong>.
</p>
<p class="mt-3 text-sm text-slsu-navy/70">
    A BARGO administrator must approve your registration before you can access protected inventory features.
</p>

<form method="POST" action="{{ route('logout') }}" class="mt-6">
    @csrf
    <button type="submit" class="btn-secondary w-full">Sign out</button>
</form>
@endsection
