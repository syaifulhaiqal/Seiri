<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An account is active only once its email is verified. If a signed-in user becomes
 * unverified (e.g. an admin deactivates them), they are signed out on their next request.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            $request->signOut();

            return to_route($request->area() . 'login')
                ->withErrors(['email' => __('Your account is not active. Please verify your email address.')])
                ->with('unverified_email', $user->email);
        }

        return $next($request);
    }
}
