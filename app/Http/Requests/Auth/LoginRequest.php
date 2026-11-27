<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Check the credentials and the account status, then sign the user in.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $admin = $this->area() === 'admin.';
        $guard = Auth::guard($admin ? 'admin' : 'web');

        $valid = $guard->validate($this->only('email', 'password'));

        /** @var User|null $user */
        $user = $guard->getLastAttempted();

        // Admins sign in only at admin/login and everyone else only at login. A wrong page gets the
        // same generic error as a wrong password, so the form never reveals which emails are admins.
        if (! $valid || $user->isAdmin() !== $admin) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        if (! $user->hasVerifiedEmail()) {
            $this->session()->flash('unverified_email', $user->email);

            throw ValidationException::withMessages([
                'email' => __('Your account is not active yet. Please confirm your email address using the link we sent you.'),
            ]);
        }

        $guard->login($user, $this->boolean('remember'));
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
