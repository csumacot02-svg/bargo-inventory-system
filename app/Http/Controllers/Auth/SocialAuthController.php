<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthAudit;
use App\Support\AuthIntent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

class SocialAuthController extends Controller
{
    /** @var list<string> */
    private array $drivers = ['google', 'facebook'];

    public function redirect(Request $request, string $provider, string $intent): RedirectResponse
    {
        AuthIntent::assertValid($intent);
        $this->assertDriver($provider);

        if (! filled(config("services.{$provider}.client_id"))) {
            return redirect()
                ->route('login.role', ['intent' => $intent])
                ->with('error', ucfirst($provider).' sign-in is not configured. Set the OAuth credentials in .env.');
        }

        if ($intent === AuthIntent::ADMIN && ! User::activeAdminExists()) {
            return redirect()->route('setup.admin');
        }

        AuthIntent::put($intent);
        session(['oauth_intent' => $intent, 'oauth_provider' => $provider]);

        $driver = Socialite::driver($provider);

        if ($provider === 'facebook') {
            $driver = $driver->scopes(['email', 'public_profile']);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->assertDriver($provider);

        $intent = session('oauth_intent', AuthIntent::get());

        if (! AuthIntent::isValid($intent)) {
            return redirect()->route('gateway')->with('error', 'Your session expired. Please select an account type again.');
        }

        AuthIntent::put($intent);

        try {
            /** @var SocialiteUser $socialUser */
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('login.role', ['intent' => $intent])
                ->with('error', ucfirst($provider).' authentication failed. Please try again.');
        }

        $email = $socialUser->getEmail();

        if (! filled($email)) {
            return redirect()
                ->route('login.role', ['intent' => $intent])
                ->with('error', ucfirst($provider).' did not provide an email address. Use email registration instead.');
        }

        $existingByProvider = User::query()
            ->where('provider', $provider)
            ->where('provider_id', (string) $socialUser->getId())
            ->first();

        if ($existingByProvider) {
            return $this->loginExisting($request, $existingByProvider, $intent, $provider);
        }

        $existingByEmail = User::query()->where('email', $email)->first();

        if ($existingByEmail) {
            // Do not insecurely merge different auth methods / providers.
            if ($existingByEmail->provider !== $provider || $existingByEmail->provider_id !== (string) $socialUser->getId()) {
                return redirect()
                    ->route('login.role', ['intent' => $intent])
                    ->with('error', 'An account already exists for '.$email
                        .'. Sign in with your original method (email/password'
                        .($existingByEmail->provider ? ' or '.$existingByEmail->provider : '')
                        .'). Accounts are not merged automatically.');
            }
        }

        if ($intent === AuthIntent::ADMIN) {
            if (! User::activeAdminExists()) {
                return redirect()->route('setup.admin');
            }

            return redirect()
                ->route('login.role', ['intent' => $intent])
                ->with('error', 'Additional administrator accounts can only be created by an existing administrator under User Management.');
        }

        $role = AuthIntent::registrationRole($intent);

        $user = new User;
        $user->forceFill([
            'name' => $socialUser->getName() ?: Str::before($email, '@'),
            'email' => $email,
            'password' => null,
            'role' => $role,
            'status' => User::STATUS_PENDING,
            'provider' => $provider,
            'provider_id' => (string) $socialUser->getId(),
            'email_verified_at' => now(),
        ])->save();

        AuthAudit::log(
            $user,
            'register_oauth',
            'Registered via '.$provider.' ('.$intent.' gateway, pending approval).',
            $request,
            $user
        );

        event(new Registered($user));

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()
            ->route('account.pending')
            ->with('success', 'Signed in with '.ucfirst($provider)
                .'. Your '.AuthIntent::label($intent)
                .' account is pending administrator approval.');
    }

    private function loginExisting(Request $request, User $user, string $intent, string $provider): RedirectResponse
    {
        if (! AuthIntent::accountMatchesIntent($user->role, $intent)) {
            return redirect()
                ->route('login.role', ['intent' => $intent])
                ->with('error', 'This account is registered as '.$user->roleLabel()
                    .'. Choose the matching account type on the previous screen.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        AuthAudit::log($user, 'login_oauth', 'Signed in via '.$provider.'.', $request);

        if ($user->isPending()) {
            return redirect()->route('account.pending');
        }

        if (in_array($user->status, [User::STATUS_REJECTED, User::STATUS_SUSPENDED], true)) {
            return redirect()->route('account.blocked');
        }

        if (! $user->isActive()) {
            Auth::logout();

            return redirect()->route('login.role', ['intent' => $intent])
                ->with('error', 'Your account cannot access the system.');
        }

        AuthIntent::clear();

        return redirect()->intended(route($user->homeRoute()));
    }

    private function assertDriver(string $provider): void
    {
        if (! in_array($provider, $this->drivers, true)) {
            abort(404);
        }
    }
}
