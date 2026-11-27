<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin roles and their permissions. Super admin only.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', [
            'role' => new Role(['permissions' => []]),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = Role::query()->create($request->validated());

        return to_route('admin.roles.index')->with('status', __('The :name role has been added. Give it to admins from the Users page.', ['name' => $role->name]));
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.form', [
            'role' => $role->load(['users' => fn ($query) => $query->orderBy('name')]),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());

        return to_route('admin.roles.index')->with('status', __('The :name role has been updated. Its admins have the new permissions straight away.', ['name' => $role->name]));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return back()->withErrors(['delete' => __('Move the admins in :name to another role before deleting it.', ['name' => $role->name])]);
        }

        $name = $role->name;
        $role->delete();

        return to_route('admin.roles.index')->with('status', __('The :name role has been deleted.', ['name' => $name]));
    }
}
