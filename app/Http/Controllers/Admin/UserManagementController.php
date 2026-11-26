<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('employee_id', 'like', $term)
                        ->orWhere('department', 'like', $term);
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'pending' => User::query()->where('status', User::STATUS_PENDING)->count(),
            'active' => User::query()->where('status', User::STATUS_ACTIVE)->count(),
            'rejected' => User::query()->where('status', User::STATUS_REJECTED)->count(),
            'suspended' => User::query()->where('status', User::STATUS_SUSPENDED)->count(),
        ];

        return view('admin.users.index', compact('users', 'counts'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:50', 'unique:users,id_number'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'confirm_admin' => ['required_if:role,admin', 'accepted'],
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

        $user = new User;
        $user->forceFill([
            'name' => $data['name'],
            'id_number' => $data['id_number'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'employee_id' => $data['employee_id'] ?? null,
            'department' => $data['department'] ?? null,
            'avatar' => $avatarPath,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ])->save();

        AuthAudit::log(
            $request->user(),
            'user_create',
            'Created '.$user->roleLabel().' account for '.$user->email.'.',
            $request,
            $user
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', $user->roleLabel().' account created. Share the password with the user through a secure channel.');
    }

    public function show(User $user): View
    {
        return view('admin.users.show', compact('user'));
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $user->forceFill(['status' => User::STATUS_ACTIVE])->save();

        AuthAudit::log($request->user(), 'user_approve', 'Approved user account.', $request, $user);

        return back()->with('success', $user->name.' has been approved and is now active.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin() && $user->isActive()) {
            return back()->with('error', 'Active administrator accounts cannot be rejected here.');
        }

        $user->forceFill(['status' => User::STATUS_REJECTED])->save();

        AuthAudit::log($request->user(), 'user_reject', 'Rejected user account.', $request, $user);

        return back()->with('success', $user->name.' has been rejected.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->isLastActiveAdmin()) {
            return back()->with('error', 'The last active administrator cannot be deactivated. Create another admin first.');
        }

        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();

        AuthAudit::log(
            $request->user(),
            'user_suspend',
            'Deactivated '.$user->roleLabel().' account (left or removed).',
            $request,
            $user
        );

        return back()->with('success', $user->name.' can no longer sign in. Inventory history was kept.');
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own account status here.');
        }

        $user->forceFill(['status' => User::STATUS_ACTIVE])->save();

        AuthAudit::log($request->user(), 'user_reactivate', 'Reactivated user account.', $request, $user);

        return back()->with('success', $user->name.' has been reactivated.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        if ($user->isAdmin()) {
            return back()->with('error', 'Use Demote to staff to remove administrator privileges.');
        }

        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_STAFF])],
        ]);

        $user->forceFill(['role' => $data['role']])->save();

        AuthAudit::log(
            $request->user(),
            'user_role_change',
            'Changed role to '.$data['role'].'.',
            $request,
            $user
        );

        return back()->with('success', 'Role updated to '.$user->roleLabel().'.');
    }

    public function demote(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot demote your own account.');
        }

        if (! $user->isAdmin()) {
            return back()->with('error', 'Only administrator accounts can be demoted to staff.');
        }

        if ($user->isLastActiveAdmin()) {
            return back()->with('error', 'The last active administrator cannot be demoted. Create another admin first.');
        }

        $user->forceFill(['role' => User::ROLE_STAFF])->save();

        AuthAudit::log(
            $request->user(),
            'user_demote',
            'Demoted administrator to staff.',
            $request,
            $user
        );

        return back()->with('success', $user->name.' is now staff. You can also suspend the account to block sign-in.');
    }

    public function assignIdNumber(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'id_number' => ['required', 'string', 'max:50', Rule::unique('users', 'id_number')->ignore($user->id)],
        ]);

        $user->forceFill(['id_number' => $data['id_number']])->save();

        AuthAudit::log(
            $request->user(),
            'user_id_number_set',
            'Set ID number for '.$user->email.'.',
            $request,
            $user
        );

        return back()->with('success', 'ID number assigned. '.$user->name.' can now sign in with it.');
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->with('error', 'Use your profile page to change your own password.');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
            'admin_password' => ['required', 'string'],
        ]);

        if (! Hash::check($data['admin_password'], $request->user()->password)) {
            return back()->withErrors([
                'admin_password' => 'Your administrator password is incorrect.',
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
        ])->save();

        AuthAudit::log(
            $request->user(),
            'user_password_reset',
            'Reset password for '.$user->email.' ('.$user->roleLabel().').',
            $request,
            $user
        );

        return back()->with(
            'success',
            'Password updated for '.$user->name.'. Share the new password through a secure channel and ask them to change it after signing in.'
        );
    }

    public function updateEmail(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'mark_verified' => ['sometimes', 'boolean'],
        ]);

        $oldEmail = $user->email;
        $newEmail = strtolower(trim($data['email']));

        if (strcasecmp($oldEmail, $newEmail) === 0) {
            return back()->with('error', 'That is already this account’s email address.');
        }

        $markVerified = $request->boolean('mark_verified');

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => $markVerified ? now() : null,
            'email_otp_hash' => null,
            'email_otp_expires_at' => null,
        ])->save();

        if (! $markVerified) {
            $user->sendEmailVerificationNotification();
        }

        AuthAudit::log(
            $request->user(),
            'user_email_change',
            'Changed email from '.$oldEmail.' to '.$newEmail
                .($markVerified ? ' (marked verified).' : ' (verification required).'),
            $request,
            $user
        );

        $message = $markVerified
            ? 'Email updated to '.$newEmail.' and marked as verified.'
            : 'Email updated to '.$newEmail.'. A 6-digit verification code was sent to the new address.';

        return back()->with('success', $message);
    }
}
