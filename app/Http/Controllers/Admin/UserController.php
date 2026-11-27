<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AdminRoleChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
            ->with('adminRole')
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
        Gate::authorize('users.edit', $user);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->pluck('name', 'id'),
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

        if ($request->assignsRoles()) {
            $user->role = UserRole::from($data['role']);
            $user->admin_role_id = $user->role === UserRole::Admin ? $data['admin_role_id'] : null;
        }

        if ($request->boolean('is_active') !== $user->hasVerifiedEmail()) {
            $user->email_verified_at = $request->boolean('is_active') ? now() : null;
        }

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $emailChanged = $user->isDirty('email');
        $accessChanged = $user->isAdmin() && $user->isDirty(['role', 'admin_role_id']);
        $user->save();

        if ($accessChanged) {
            $user->notify(new AdminRoleChanged($user->isSuperAdmin() ? null : $user->load('adminRole')->adminRole));
        }

        if ($emailChanged && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return to_route('admin.users.edit', $user)->with('status', __('User updated.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('users.edit', $user);

        if ($user->is($request->user())) {
            return back()->withErrors(['delete' => __('You cannot delete your own account.')]);
        }

        $name = $user->name;
        $user->delete();

        return to_route('admin.users.index')->with('status', __(':name has been deleted.', ['name' => $name]));
    }
}
