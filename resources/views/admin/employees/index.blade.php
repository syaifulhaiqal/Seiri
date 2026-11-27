<x-layouts.app :title="__('Employees')">
    <div class="page-header">
        <div>
            <h1>{{ __('Employees') }}</h1>
            <p class="text-secondary mb-0">{{ trans_choice(':count employee|:count employees', $employees->total()) }}</p>
        </div>
        @can('employees.manage')
            <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add employee') }}
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.employees.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search name, email, number or job title') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select name="department" class="form-select" aria-label="{{ __('Department') }}" x-on:change="submit()">
                        <option value="">{{ __('All departments') }}</option>
                        @foreach ($departments as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['department'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\EmployeeStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-3 d-flex gap-2 justify-content-lg-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Employee') }}</th>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Department') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Joined') }}</th>
                        <th class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.employees.show', $employee) }}" class="d-flex align-items-center gap-2 text-decoration-none text-body">
                                    <span class="avatar">{{ str($employee->name)->substr(0, 1) }}</span>
                                    <div class="min-w-0">
                                        <div class="fw-medium">{{ $employee->name }}</div>
                                        <div class="small text-secondary">{{ $employee->job_title }}</div>
                                    </div>
                                </a>
                            </td>
                            <td class="text-nowrap small">{{ $employee->employee_no }}</td>
                            <td>{{ $employee->department?->name ?? '—' }}</td>
                            <td><x-enum-badge :value="$employee->employment_type" /></td>
                            <td><x-enum-badge :value="$employee->status" /></td>
                            <td class="text-secondary small text-nowrap">{{ $employee->join_date->translatedFormat('j M Y') }}</td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('View') }}">
                                    <i class="bi bi-eye"></i><span class="visually-hidden">{{ __('View') }}</span>
                                </a>
                                @can('employees.manage')
                                    <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('No employees match your filters.') : __('No employees yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="card-body border-top pb-0">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
