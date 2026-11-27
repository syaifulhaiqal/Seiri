<x-layouts.app :title="__('Users')">
    <div class="page-header">
        <div>
            <h1>{{ __('Users') }}</h1>
            <p class="text-secondary mb-0">{{ trans_choice(':count account|:count accounts', $users->total()) }}</p>
        </div>
    </div>

    <div x-data="deleteUser">
        <div class="card">
            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                            <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search name, email or phone') }}" aria-label="{{ __('Search') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="role" class="form-select" aria-label="{{ __('Role') }}" x-on:change="submit()">
                            <option value="">{{ __('All roles') }}</option>
                            @foreach (\App\Enums\UserRole::options() as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                            <option value="">{{ __('All statuses') }}</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('Pending email') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-3 d-flex gap-2 justify-content-lg-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                        @if (array_filter($filters))
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">{{ __('User') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Joined') }}</th>
                            <th class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar">{{ str($user->name)->substr(0, 1) }}</span>
                                        <div class="min-w-0">
                                            <div class="fw-medium">
                                                {{ $user->name }}
                                                @if ($user->is(auth()->user()))
                                                    <span class="text-secondary small">({{ __('you') }})</span>
                                                @endif
                                            </div>
                                            <div class="small text-secondary">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-nowrap">{{ $user->phone_number ?: '—' }}</td>
                                <td>
                                    <x-role-badge :role="$user->role" />
                                    @if ($user->adminRole)
                                        <div class="small text-secondary">{{ $user->adminRole->name }}</div>
                                    @endif
                                </td>
                                <td><x-status-badge :user="$user" /></td>
                                <td class="text-secondary small text-nowrap">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                                <td class="text-end pe-3 text-nowrap">
                                    @can('users.edit', $user)
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                    </a>
                                    @endcan
                                    @if (! $user->is(auth()->user()) && auth()->user()->can('users.edit', $user))
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="{{ __('Delete') }}"
                                            x-on:click="open(@js($user->name), @js(route('admin.users.destroy', $user)))"
                                        >
                                            <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Delete') }}</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-5">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    {{ __('No users match your filters.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="card-body border-top pb-0">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        @include('admin.users.partials.delete-modal')
    </div>
</x-layouts.app>
