<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthAudit;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class FirstAdminSetupController extends Controller
{
    public function create(): View
    {
        $inactiveAdmins = User::inactiveAdmins();

        return view('setup.admin', [
            'isRecovery' => $inactiveAdmins->isNotEmpty(),
            'inactiveAdmins' => $inactiveAdmins,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (User::activeAdminExists()) {
            return redirect()->route('gateway')->with('error', 'Administrator setup is closed.');
        }

        $isRecovery = User::inactiveAdmins()->isNotEmpty();

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:50', 'unique:users,id_number'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'confirmed', Password::defaults()],
        ]);

        // Role is never taken from the request — always admin for this protected setup.
        $admin = new User;
        $admin->forceFill([
            'name'      => $data['name'],
            'id_number' => $data['id_number'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'role'      => User::ROLE_ADMIN,
            'status'    => User::STATUS_ACTIVE,
            'department' => 'Business Affairs and Resource Generating Office',
        ])->save();

        event(new Registered($admin));

        AuthAudit::log(
            $admin,
            $isRecovery ? 'admin_recovery' : 'admin_setup',
            $isRecovery
                ? 'Emergency administrator account created (no active admin).'
                : 'First administrator account created.',
            $request,
            $admin
        );

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()
            ->route('verification.notice')
            ->with('success', 'Administrator account created. Enter the 6-digit code sent to your email to continue.');
    }
}
