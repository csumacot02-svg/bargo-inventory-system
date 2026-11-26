@extends('layouts.guest')

@section('title', 'Access Unavailable')
@section('auth_subtitle', 'Account restricted')

@section('auth_content')
<h1 class="font-display text-2xl font-semibold text-slsu-navy">Access unavailable</h1>
<p class="mt-2 text-sm text-slsu-navy/70">
    Your account status is <strong>{{ $user->statusLabel() }}</strong>.
    You cannot use the inventory system while this status remains.
</p>
<p class="mt-3 text-sm text-slsu-navy/70">
    Contact the BARGO office if you believe this is a mistake.
</p>

<form method="POST" action="{{ route('logout') }}" class="mt-6">
    @csrf
    <button type="submit" class="btn-secondary w-full">Sign out</button>
</form>
@endsection
