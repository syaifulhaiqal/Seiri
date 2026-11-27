<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureAccountIsActive::class,
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route($request->area() . 'login'));
        $middleware->redirectUsersTo(fn (Request $request) => route($request->area() . 'dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every login rotates the CSRF token, so a login page left open in another tab (e.g. the
        // admin one while signing in as a user) expires. Send it back to a fresh form, not a bare 419.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && $request->routeIs('login.store', 'admin.login.store')) {
                return to_route($request->area() . 'login')
                    ->withInput($request->only('email', 'remember'))
                    ->with('status', __('Your session was refreshed. Please log in again.'));
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
