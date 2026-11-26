@extends('layouts.base')

@section('body')
<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/slsu-campus.png') }}');"></div>
    <div class="absolute inset-0 bg-gradient-to-br from-slsu-navy/88 via-slsu-navy/78 to-slsu-maroon/70"></div>

    <div class="relative w-full max-w-lg rounded-2xl border border-white/20 bg-slsu-cream p-8 shadow-2xl sm:p-10">
        <div class="mb-6 flex items-center gap-3">
            <img src="{{ asset('images/slsu-logo.png') }}" alt="SLSU Logo" class="h-14 w-14 rounded-full bg-white p-1 shadow">
            <div>
                <p class="font-display text-xl font-semibold text-slsu-navy">SLSU BARGO</p>
                <p class="text-xs text-slsu-maroon">@yield('auth_subtitle', 'Inventory System')</p>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-slsu-green/30 bg-slsu-green/10 px-4 py-3 text-sm text-slsu-green">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-slsu-maroon/30 bg-slsu-maroon/10 px-4 py-3 text-sm text-slsu-maroon">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-slsu-maroon/30 bg-slsu-maroon/10 px-4 py-3 text-sm text-slsu-maroon">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('auth_content')
    </div>
</div>
@endsection
