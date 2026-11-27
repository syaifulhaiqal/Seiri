<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->search($filters['search'] ?? null)
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, string $status) => match ($status) {
                'active' => $query->whereNotNull('email_verified_at'),
                'inactive' => $query->whereNull('email_verified_at'),
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
        ]);
        $user->role = UserRole::from($data['role']);

        if ($request->boolean('is_active') !== $user->hasVerifiedEmail()) {
            $user->email_verified_at = $request->boolean('is_active') ? now() : null;
        }

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $emailChanged = $user->isDirty('email');
        $user->save();

        if ($emailChanged && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return to_route('admin.users.edit', $user)->with('status', __('User updated.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['delete' => __('You cannot delete your own account.')]);
        }

        $name = $user->name;
        $user->delete();

        return to_route('admin.users.index')->with('status', __(':name has been deleted.', ['name' => $name]));
    }
}
