<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? to_route('dashboard') : view('welcome'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('register', [Controllers\Auth\RegisterController::class, 'create'])->name('register');
    Route::post('register', [Controllers\Auth\RegisterController::class, 'store'])->name('register.store');

    Route::post('email/resend', [Controllers\Auth\EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');
});

// The activation link from the email. Signed, so it works without being logged in.
Route::get('email/verify/{user}/{hash}', [Controllers\Auth\EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Two sign-in areas sharing the same controllers: users at /login (`web` guard) and admins at
// /admin/login (`admin` guard). Each guard has its own session key, so both can be signed in at once.
foreach (['web' => '', 'admin' => 'admin'] as $guard => $area) {
    Route::prefix($area)->name($area ? "{$area}." : '')->group(function () use ($guard) {
        Route::middleware("guest:{$guard}")->group(function () {
            Route::get('login', [Controllers\Auth\LoginController::class, 'create'])->name('login');
            Route::post('login', [Controllers\Auth\LoginController::class, 'store'])->name('login.store');
        });

        Route::middleware(["auth:{$guard}", 'active'])->group(function () use ($guard) {
            Route::get('dashboard', Controllers\DashboardController::class)->name('dashboard');

            Route::get('profile', [Controllers\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [Controllers\ProfileController::class, 'update'])->name('profile.update');
            Route::put('profile/password', [Controllers\ProfileController::class, 'updatePassword'])->name('profile.update_password');

            Route::post('logout', [Controllers\Auth\LoginController::class, 'destroy'])->name('logout');

            if ($guard === 'admin') {
                Route::middleware('admin')->group(function () {
                    Route::resource('users', Controllers\Admin\UserController::class)->only(['index', 'edit', 'update', 'destroy']);
                });
            }
        });
    });
}
