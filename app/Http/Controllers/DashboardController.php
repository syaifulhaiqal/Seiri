<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $isAdmin = $request->user()->isAdmin();

        $stats = $isAdmin
            ? [
                'total' => User::query()->count(),
                'active' => User::query()->whereNotNull('email_verified_at')->count(),
                'inactive' => User::query()->whereNull('email_verified_at')->count(),
                'admins' => User::query()->where('role', UserRole::Admin)->count(),
            ]
            : null;

        return view('dashboard', [
            'user' => $request->user(),
            'stats' => $stats,
            'recentUsers' => $isAdmin ? User::query()->latest()->take(5)->get() : collect(),
        ]);
    }
}
