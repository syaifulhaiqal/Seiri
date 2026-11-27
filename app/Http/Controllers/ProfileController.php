<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if (! $user->isDirty('email')) {
            $user->save();

            return to_route($request->area() . 'profile.edit')->with('status', __('Profile updated.'));
        }

        // A new email must be confirmed again before the account is active.
        $user->email_verified_at = null;
        $user->save();
        $user->sendEmailVerificationNotification();

        $request->signOut();

        return to_route($request->area() . 'login')->with(
            'status',
            __('Your email was changed. We sent an activation link to :email. Please confirm it, then log in again.', ['email' => $user->email]),
        );
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return to_route($request->area() . 'profile.edit')->with('status', __('Password updated.'));
    }
}
