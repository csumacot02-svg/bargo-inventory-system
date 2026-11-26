<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthIntent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GatewayController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (auth()->check()) {
            $user = auth()->user();

            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }
            if ($user->isPending()) {
                return redirect()->route('account.pending');
            }
            if (in_array($user->status, [User::STATUS_REJECTED, User::STATUS_SUSPENDED], true)) {
                return redirect()->route('account.blocked');
            }
            if ($user->isActive()) {
                return redirect()->route($user->homeRoute());
            }
        }

        return view('auth.gateway', [
            'adminExists' => User::activeAdminExists(),
        ]);
    }

    public function select(string $intent): RedirectResponse
    {
        AuthIntent::assertValid($intent);
        AuthIntent::put($intent);

        // First / recovery administrator uses protected setup — never open public admin registration.
        if ($intent === AuthIntent::ADMIN && ! User::activeAdminExists()) {
            return redirect()
                ->route('setup.admin')
                ->with('success', User::inactiveAdmins()->isNotEmpty()
                    ? 'No active administrator can sign in. Complete recovery setup or restore an existing admin.'
                    : 'No administrator exists yet. Complete the protected first-admin setup.');
        }

        if ($intent === AuthIntent::BARGO && ! User::activeAdminExists()) {
            return redirect()
                ->route('setup.admin')
                ->with('success', 'No active administrator exists yet. Complete first-admin setup, then sign in here.');
        }

        return redirect()->route('login.role', ['intent' => $intent]);
    }
}
