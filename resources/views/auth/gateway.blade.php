@extends('layouts.base')

@section('title', 'Sign In — SLSU BARGO')

@section('body')
<div class="gateway-page">
    <div class="gateway-bg" aria-hidden="true">
        <div class="gateway-bg__image" style="background-image: url('{{ asset('images/slsu-campus.png') }}');"></div>
        <div class="gateway-bg__overlay"></div>
        <div class="gateway-bg__vignette"></div>
    </div>

    <div class="gateway-shell">
        <main class="gateway-main">
            <div class="gateway-stack">
                <header class="gateway-identity">
                    <div class="gateway-identity__banner">
                        <img
                            src="{{ asset('images/slsu-header.png') }}"
                            alt="Southern Leyte State University"
                            class="gateway-identity__slsu"
                            width="320"
                            height="56"
                        >
                        <img
                            src="{{ asset('images/bagong-pilipinas.svg') }}"
                            alt="Bagong Pilipinas"
                            class="gateway-identity__bp"
                            width="120"
                            height="48"
                        >
                    </div>
                    <p class="gateway-identity__office">Business Affairs &amp; Resource Generating Office</p>
                    <h1 class="gateway-identity__brand">SLSU BARGO Inventory</h1>
                    <p class="gateway-identity__campus">Hinunangan Campus</p>
                </header>

                @if (session('success'))
                    <div class="gateway-alert gateway-alert--success" role="status">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="gateway-alert gateway-alert--error" role="alert">{{ session('error') }}</div>
                @endif

                <section class="gateway-signin-panel" aria-labelledby="gateway-signin-heading">
                    <div class="gateway-signin-panel__head">
                        @unless ($adminExists)
                            <span class="gateway-chip gateway-chip--warn">First-time setup required</span>
                        @else
                            <span class="gateway-chip">Administrator &amp; Staff</span>
                        @endunless
                        <h2 id="gateway-signin-heading" class="gateway-signin-panel__title">Operations portal</h2>
                        <p class="gateway-signin-panel__lede">
                            One secure sign-in for daily BARGO work—stock, requests, payments, and official Payment Orders.
                        </p>
                    </div>

                    <ul class="gateway-feature-list">
                        <li>
                            <span class="gateway-feature-list__icon" aria-hidden="true">✓</span>
                            <span>Front desk runs without waiting for an administrator on site</span>
                        </li>
                        <li>
                            <span class="gateway-feature-list__icon" aria-hidden="true">✓</span>
                            <span>Approve, release, and record payments from the same portal</span>
                        </li>
                        <li>
                            <span class="gateway-feature-list__icon" aria-hidden="true">✓</span>
                            <span>Administrators retain user management and audit controls</span>
                        </li>
                    </ul>

                    <form
                        method="POST"
                        action="{{ route('gateway.select', ['intent' => 'bargo']) }}"
                        class="gateway-signin-form"
                    >
                        @csrf
                        <button
                            type="submit"
                            class="gateway-signin-btn"
                            data-gateway-submit
                        >
                            Sign in to BARGO
                        </button>
                    </form>
                </section>

                @unless ($adminExists)
                    <p class="gateway-setup-link">
                        <a href="{{ route('setup.admin') }}">Set up the first administrator account</a>
                        <span class="gateway-setup-link__note">Required before staff registrations can be approved.</span>
                    </p>
                @endunless
            </div>
        </main>

        <footer class="gateway-footer">
            <p>© {{ date('Y') }} Southern Leyte State University — Hinunangan Campus</p>
            <p class="gateway-footer__tag">BARGO Inventory Management System</p>
        </footer>
    </div>
</div>
@push('scripts')
<script>
    document.querySelector('[data-gateway-submit]')?.closest('form')?.addEventListener('submit', function () {
        const btn = this.querySelector('[data-gateway-submit]');
        if (btn) {
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.textContent = 'Opening sign-in…';
        }
    });
</script>
@endpush
@endsection
