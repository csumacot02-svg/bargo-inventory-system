@php
    $googleConfigured = filled(config('services.google.client_id'));
    $facebookConfigured = filled(config('services.facebook.client_id'));
@endphp

@if ($googleConfigured || $facebookConfigured)
    <div class="my-5 flex items-center gap-3" role="separator" aria-label="Or continue with social account">
        <div class="h-px flex-1 bg-slsu-navy/15"></div>
        <span class="text-xs font-semibold tracking-[0.18em] text-slsu-navy/45 uppercase">OR</span>
        <div class="h-px flex-1 bg-slsu-navy/15"></div>
    </div>

    <div class="space-y-2.5">
        @if ($googleConfigured)
            <a
                href="{{ route('auth.social.redirect', ['provider' => 'google', 'intent' => $intent]) }}"
                class="inline-flex w-full items-center justify-center gap-3 rounded-md border border-slsu-navy/15 bg-white px-4 py-2.5 text-sm font-semibold text-slsu-navy transition hover:bg-slsu-sand focus:outline-none focus:ring-2 focus:ring-slsu-blue focus:ring-offset-2"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#EA4335" d="M12 10.2v3.6h5.1c-.2 1.2-1.5 3.6-5.1 3.6-3.1 0-5.6-2.5-5.6-5.6S8.9 6.2 12 6.2c1.8 0 3 .7 3.7 1.4l2.5-2.4C16.8 3.8 14.6 2.8 12 2.8 6.9 2.8 2.8 6.9 2.8 12S6.9 21.2 12 21.2c5.5 0 9.1-3.9 9.1-9.3 0-.6-.1-1.1-.2-1.7H12z"/>
                    <path fill="#34A853" d="M3.9 7.6l3 2.2C7.7 7.6 9.7 6.2 12 6.2c1.8 0 3 .7 3.7 1.4l2.5-2.4C16.8 3.8 14.6 2.8 12 2.8 8.4 2.8 5.3 4.8 3.9 7.6z"/>
                    <path fill="#4A90E2" d="M12 21.2c2.5 0 4.6-.8 6.1-2.2l-3-2.4c-.8.6-1.9 1-3.1 1-3.5 0-4.9-2.4-5.1-3.6l-3 2.3C5.3 19.2 8.4 21.2 12 21.2z"/>
                    <path fill="#FBBC05" d="M3.9 16.4l3-2.3c.3 1.1 1.7 3.6 5.1 3.6 1.2 0 2.3-.4 3.1-1l3 2.4C16.6 20.4 14.5 21.2 12 21.2c-3.6 0-6.7-2-8.1-4.8z"/>
                </svg>
                Continue with Google
            </a>
        @endif

        @if ($facebookConfigured)
            <a
                href="{{ route('auth.social.redirect', ['provider' => 'facebook', 'intent' => $intent]) }}"
                class="inline-flex w-full items-center justify-center gap-3 rounded-md border border-[#1877F2]/30 bg-[#1877F2] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#166FE5] focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:ring-offset-2"
            >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94z"/>
                </svg>
                Continue with Facebook
            </a>
        @endif
    </div>
@else
    <p class="mt-5 rounded-lg border border-slsu-navy/10 bg-slsu-sand/50 px-3 py-2 text-xs text-slsu-navy/60">
        Google and Facebook sign-in are available once OAuth credentials are configured in <code class="font-semibold">.env</code>.
    </p>
@endif
