<x-layouts.app :title="__('Roles & permissions')">
    <div class="page-header">
        <div>
            <h1>{{ __('Roles & permissions') }}</h1>
            <p class="text-secondary mb-0">{{ __('A role is a set of modules and actions. Give each admin one role from the Users page.') }}</p>
        </div>
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Add role') }}
        </a>
    </div>

    <div x-data="confirmDelete">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">{{ __('Role') }}</th>
                            <th>{{ __('Can use') }}</th>
                            <th class="text-end">{{ __('Admins') }}</th>
                            <th class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-subtle">
                            <td class="ps-3">
                                <div class="fw-medium">{{ __('Super admin') }}</div>
                                <div class="small text-secondary">{{ __('The owner, e.g. the CEO. Built in and cannot be changed.') }}</div>
                            </td>
                            <td class="small">{{ __('Everything, including roles and other admins') }}</td>
                            <td class="text-end">{{ \App\Models\User::query()->where('role', \App\Enums\UserRole::SuperAdmin)->count() }}</td>
                            <td class="pe-3"></td>
                        </tr>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="fw-medium text-decoration-none">{{ $role->name }}</a>
                                    @if ($role->description)
                                        <div class="small text-secondary">{{ $role->description }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $role->permissions->map(fn ($permission) => $permission->module())->unique()->implode(', ') }}
                                    <div class="text-secondary">{{ trans_choice(':count permission|:count permissions', $role->permissions->count()) }}</div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.index', ['role' => 'admin']) }}" class="text-decoration-none text-body">{{ $role->users_count }}</a>
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                    </a>
                                    @if ($role->users_count === 0)
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}" x-on:click="open(@js($role->name), @js(route('admin.roles.destroy', $role)))">
                                            <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Delete') }}</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-5">{{ __('No roles yet. Add one, then give it to admins.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-delete-modal :title="__('Delete role?')" :button="__('Delete role')">
            {{ __('This permanently deletes the role') }} <strong x-text="name"></strong>.
        </x-delete-modal>
    </div>
</x-layouts.app>
