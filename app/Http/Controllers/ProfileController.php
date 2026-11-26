<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuthAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->hasFile('avatar') && ! $request->file('avatar')->isValid()) {
            $uploadError = $request->file('avatar')->getError();
            $message = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The photo is too large. Please use a JPG or PNG under 5 MB.',
                default => 'The photo failed to upload. Please try a smaller JPG or PNG file.',
            };

            return back()->withInput()->withErrors(['avatar' => $message]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:50', 'unique:users,id_number,'.$user->id],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'avatar.mimes' => 'The photo must be a JPG, JPEG, PNG, or WEBP file.',
            'avatar.max' => 'The photo must be smaller than 5 MB.',
            'avatar.uploaded' => 'The photo failed to upload. Please try a smaller JPG or PNG under 5 MB.',
        ]);

        if ($request->hasFile('avatar')) {
            try {
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->withErrors([
                    'avatar' => 'Could not save the photo. Please try again with a different image.',
                ]);
            }
        } else {
            unset($data['avatar']);
        }

        $user->forceFill($data)->save();

        AuthAudit::log($user, 'profile_update', 'Updated own profile.', $request, $user);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }
}
