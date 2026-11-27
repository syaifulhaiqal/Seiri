<x-layouts.app :title="__('Departments')">
    <div class="page-header">
        <div>
            <h1>{{ __('Departments') }}</h1>
            <p class="text-secondary mb-0">{{ trans_choice(':count department|:count departments', $departments->total()) }}</p>
        </div>
        <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Add department') }}
        </a>
    </div>

    <div x-data="confirmDelete">
        <div class="card">
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('admin.departments.index') }}" class="row g-2 align-items-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                            <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search name or code') }}" aria-label="{{ __('Search') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-7 d-flex gap-2 justify-content-md-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                        @if (array_filter($filters))
                            <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">{{ __('Department') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Active employees') }}</th>
                            <th class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($departments as $department)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-medium">{{ $department->name }}</div>
                                    @if ($department->description)
                                        <div class="small text-secondary">{{ $department->description }}</div>
                                    @endif
                                </td>
                                <td><span class="badge rounded-pill badge-soft badge-soft-secondary">{{ $department->code }}</span></td>
                                <td>
                                    <a href="{{ route('admin.employees.index', ['department' => $department->id]) }}" class="text-decoration-none">
                                        {{ number_format($department->employees_count) }}
                                    </a>
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="{{ route('admin.departments.edit', $department) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="{{ __('Delete') }}"
                                        x-on:click="open(@js($department->name), @js(route('admin.departments.destroy', $department)))"
                                    >
                                        <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Delete') }}</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-5">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    {{ array_filter($filters) ? __('No departments match your search.') : __('No departments yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($departments->hasPages())
                <div class="card-body border-top pb-0">
                    {{ $departments->links() }}
                </div>
            @endif
        </div>

        <x-delete-modal :title="__('Delete department?')" :button="__('Delete department')">
            {{ __('This deletes') }} <strong x-text="name"></strong>. {{ __('Its employees stay, without a department.') }}
        </x-delete-modal>
    </div>
</x-layouts.app>
