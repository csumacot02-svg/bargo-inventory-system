<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthAudit;
use App\Support\AuthIntent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(?string $intent = null): View|RedirectResponse
    {
        if ($intent !== null) {
            AuthIntent::put($intent);
        }

        $intent = AuthIntent::get();

        if ($intent === null) {
            return redirect()->route('gateway');
        }

        if ($intent === AuthIntent::ADMIN && ! User::activeAdminExists()) {
            return redirect()->route('setup.admin');
        }

        return view('auth.login', [
            'intent' => $intent,
            'intentLabel' => AuthIntent::label($intent),
            'socialEnabled' => $this->socialEnabled(),
            'allowRegistration' => AuthIntent::allowsSelfRegistration($intent),
        ]);
    }

    public function store(Request $request, ?string $intent = null): RedirectResponse
    {
        if ($intent !== null) {
            AuthIntent::put($intent);
        }

        $intent = AuthIntent::require();

        $request->validate([
            'id_number' => ['required', 'string'],
            'password'  => ['required', 'string'],
        ]);

        $idNumber = $request->string('id_number')->trim()->toString();

        $existing = User::query()->where('id_number', $idNumber)->first();

        if ($existing && ! $existing->usesPassword()) {
            AuthAudit::log(null, 'login_failed', 'Password sign-in blocked for OAuth account with ID '.$idNumber.'.', $request);

            return back()
                ->withInput($request->only('id_number', 'remember'))
                ->withErrors([
                    'id_number' => 'This account signs in with '.ucfirst((string) $existing->provider)
                        .'. Use the social sign-in option below.',
                ]);
        }

        // Auth::attempt requires the unique column used in the guard; map id_number → email via the user record.
        if (! $existing || ! Auth::attempt(['email' => $existing->email, 'password' => $request->string('password')->toString()], $request->boolean('remember'))) {
            AuthAudit::log(null, 'login_failed', 'Failed sign-in for ID '.$idNumber.'.', $request);

            return back()
                ->withInput($request->only('id_number', 'remember'))
                ->withErrors(['id_number' => 'Invalid ID number or password. Please try again.']);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        // UX check: account type page must match the stored role (cannot elevate privileges).
        if (! AuthIntent::accountMatchesIntent($user->role, $intent)) {
            AuthAudit::log($user, 'login_wrong_intent', $user->name.' tried to sign in via the '.$intent.' gateway.', $request);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('id_number'))
                ->withErrors([
                    'id_number' => 'This account is registered as '.$user->roleLabel()
                        .'. Go back and choose the matching account type.',
                ]);
        }

        AuthAudit::log($user, 'login', $user->name.' signed in via '.$intent.' gateway.', $request);

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->isPending()) {
            return redirect()->route('account.pending');
        }

        if (in_array($user->status, [User::STATUS_REJECTED, User::STATUS_SUSPENDED], true)) {
            return redirect()->route('account.blocked');
        }

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['id_number' => 'Your account cannot access the system.']);
        }

        AuthIntent::clear();

        return redirect()->intended(route($user->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($user = Auth::user()) {
            AuthAudit::log($user, 'logout', $user->name.' signed out.', $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        AuthIntent::clear();

        return redirect()->route('gateway');
    }

    private function socialEnabled(): bool
    {
        return filled(config('services.google.client_id'))
            || filled(config('services.facebook.client_id'));
    }
}
