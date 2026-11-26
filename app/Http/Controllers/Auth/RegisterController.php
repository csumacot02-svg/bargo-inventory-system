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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
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

        if ($intent === AuthIntent::ADMIN) {
            if (! User::activeAdminExists()) {
                return redirect()->route('setup.admin');
            }

            return redirect()
                ->route('login.role', ['intent' => AuthIntent::ADMIN])
                ->with('error', 'Additional administrator accounts can only be created by an existing administrator under User Management.');
        }

        if ($intent === AuthIntent::BARGO) {
            $intent = AuthIntent::STAFF;
            AuthIntent::put($intent);
        }

        return view('auth.register', [
            'intent' => $intent,
            'intentLabel' => AuthIntent::label($intent),
            'socialEnabled' => filled(config('services.google.client_id')) || filled(config('services.facebook.client_id')),
            'adminExists' => User::activeAdminExists(),
        ]);
    }

    public function store(Request $request, ?string $intent = null): RedirectResponse
    {
        if ($intent !== null) {
            AuthIntent::put($intent);
        }

        $intent = AuthIntent::require();

        if ($intent === AuthIntent::ADMIN) {
            if (! User::activeAdminExists()) {
                return redirect()->route('setup.admin');
            }

            return redirect()
                ->route('login.role', ['intent' => AuthIntent::ADMIN])
                ->with('error', 'Additional administrator accounts can only be created by an existing administrator.');
        }

        if ($intent === AuthIntent::BARGO) {
            AuthIntent::put(AuthIntent::STAFF);
            $intent = AuthIntent::STAFF;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:50', 'unique:users,id_number'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'avatar.mimes' => 'The photo must be a JPG, JPEG, PNG, or WEBP file.',
            'avatar.max' => 'The photo must be smaller than 5 MB.',
            'avatar.uploaded' => 'The photo failed to upload. Please try a smaller JPG or PNG under 5 MB.',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            if (! $request->file('avatar')->isValid()) {
                return back()->withInput()->withErrors([
                    'avatar' => 'The photo is too large or failed to upload. Please use a JPG or PNG under 5 MB.',
                ]);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        // Role comes ONLY from server-side session intent — never from the request body.
        $role = AuthIntent::registrationRole($intent);

        $user = new User;
        $user->forceFill([
            'name' => $data['name'],
            'id_number' => $data['id_number'],
            'email' => $data['email'],
            'password' => $data['password'],
            'avatar' => $avatarPath,
            'role' => $role,
            'status' => User::STATUS_PENDING,
            'provider' => null,
            'provider_id' => null,
        ])->save();

        event(new Registered($user));

        AuthAudit::log(
            $user,
            'register',
            'Registered via '.$intent.' gateway (pending approval).',
            $request,
            $user
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('verification.notice')
            ->with('success', 'Account created as '.AuthIntent::label($intent)
                .'. Enter the 6-digit code sent to your email. An administrator must approve access before the account becomes active.');
    }
}
