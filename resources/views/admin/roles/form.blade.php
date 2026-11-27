@php
    $editing = $role->exists;
    $checked = collect(old('permissions', $role->permissions->map(fn ($permission) => $permission->value)->all()));
@endphp

<x-layouts.app :title="$editing ? __('Edit :name', ['name' => $role->name]) : __('Add role')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.roles.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to roles & permissions') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit role') : __('Add role') }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <x-form.input name="name" :label="__('Name')" :value="$role->name" required :placeholder="__('e.g. HR manager')" />
                        <x-form.input name="description" :label="__('Description')" :value="$role->description" />
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Permissions') }}</h2>
                    </div>
                    @error('permissions')
                        <div class="alert alert-danger mb-0 rounded-0 border-0 border-bottom">{{ $message }}</div>
                    @enderror
                    <div class="card-body">
                        <div class="row g-4">
                            @foreach (\App\Enums\Permission::grouped() as $module => $permissions)
                                <div class="col-md-6">
                                    <div class="fw-semibold mb-2">{{ $module }}</div>
                                    @foreach ($permissions as $value => $label)
                                        @php($permission = \App\Enums\Permission::from($value))
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $value }}" id="permission-{{ $value }}" @checked($checked->contains($value))>
                                            <label class="form-check-label" for="permission-{{ $value }}">
                                                {{ $label }}
                                                @if ($permission->includes())
                                                    <span class="d-block small text-secondary">{{ __('Includes: :list', ['list' => collect($permission->includes())->map->label()->implode(', ')]) }}</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            @if ($editing)
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h2>{{ __('Admins with this role') }}</h2>
                        </div>
                        <ul class="list-group list-group-flush">
                            @forelse ($role->users as $member)
                                <li class="list-group-item small">
                                    <a href="{{ route('admin.users.edit', $member) }}">{{ $member->name }}</a>
                                    <div class="text-secondary">{{ $member->email }}</div>
                                </li>
                            @empty
                                <li class="list-group-item small text-secondary">{{ __('Nobody yet. Assign it from the Users page.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="d-flex gap-2">
            <x-submit-button>{{ $editing ? __('Save changes') : __('Add role') }}</x-submit-button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
