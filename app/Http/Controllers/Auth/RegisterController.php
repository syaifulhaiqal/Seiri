<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Create the account and send the activation email. The user is NOT signed in:
     * they must confirm their email first, then log in.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = new User($request->validated());
        $user->role = UserRole::User;
        $user->save();

        // Laravel's built-in listener sends the verification email for this event.
        event(new Registered($user));

        return to_route('login')->with(
            'status',
            __('Account created! We sent an activation link to :email. Please confirm your email before logging in.', ['email' => $user->email]),
        );
    }
}
