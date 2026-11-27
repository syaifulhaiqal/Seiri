<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.login', ['admin' => $request->area() === 'admin.']);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route($request->area() . 'dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->signOut();

        return to_route($request->area() . 'login')->with('status', __('You have been signed out.'));
    }
}
