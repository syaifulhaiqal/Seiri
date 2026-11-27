<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;

/**
 * Verification works for signed-out users: the link in the email is a signed URL,
 * so the user does not need to be logged in to activate their account.
 */
class EmailVerificationController extends Controller
{
    public function verify(User $user, string $hash): RedirectResponse
    {
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if ($user->hasVerifiedEmail()) {
            return to_route('login')->with('status', __('Your account is already active. You can log in.'));
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return to_route('login')->with('status', __('Your email is confirmed and your account is now active. You can log in.'));
    }

    public function resend(ResendVerificationRequest $request): RedirectResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // Same message either way, so this form cannot be used to check which emails are registered.
        return to_route('login')->with('status', __('If that account exists and is not active yet, a new activation link has been sent.'));
    }
}
