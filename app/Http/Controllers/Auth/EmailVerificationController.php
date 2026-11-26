<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuthAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->afterVerifiedRedirect($request);
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->afterVerifiedRedirect($request);
        }

        $data = $request->validate([
            'otp' => ['required', 'regex:/^\d{6}$/'],
        ], [
            'otp.required' => 'Enter the 6-digit code sent to your email.',
            'otp.regex' => 'The verification code must be 6 digits (including leading zeros).',
        ]);

        // Cast explicitly so "051921" stays a string, not 51921.
        $otp = (string) $data['otp'];

        if (! $request->user()->verifyEmailOtp($otp)) {
            AuthAudit::log($request->user(), 'email_otp_failed', 'Invalid or expired email OTP.', $request);

            return back()
                ->withInput(['otp' => $otp])
                ->withErrors(['otp' => 'That code is invalid or has expired. Request a new code and try again.']);
        }

        AuthAudit::log($request->user(), 'email_verified', 'Email address verified with OTP.', $request, $request->user());

        return $this->afterVerifiedRedirect($request)
            ->with('success', 'Email verified successfully.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->afterVerifiedRedirect($request);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'A new 6-digit code has been sent to your email.');
    }

    private function afterVerifiedRedirect(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isPending()) {
            return redirect()->route('account.pending');
        }

        if (in_array($user->status, ['rejected', 'suspended'], true)) {
            return redirect()->route('account.blocked');
        }

        return redirect()->intended(route($user->homeRoute()));
    }
}
