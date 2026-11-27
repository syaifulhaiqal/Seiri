<x-layouts.app :title="$employee->name">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.employees.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to employees') }}
            </a>
            <div class="d-flex align-items-center gap-3 mt-2">
                <span class="avatar avatar-lg">{{ str($employee->name)->substr(0, 1) }}</span>
                <div>
                    <h1 class="mb-1">{{ $employee->name }}</h1>
                    <p class="text-secondary mb-0">
                        {{ $employee->job_title }}
                        @if ($employee->department)
                            · {{ $employee->department->name }}
                        @endif
                        · <x-enum-badge :value="$employee->status" />
                    </p>
                </div>
            </div>
        </div>
        <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>{{ __('Edit employee') }}
        </a>
    </div>

    <div class="row g-4" x-data="confirmDelete">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>{{ __('Details') }}</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-secondary fw-normal">{{ __('Number') }}</dt>
                        <dd class="col-7">{{ $employee->employee_no }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Email') }}</dt>
                        <dd class="col-7 text-break">{{ $employee->email }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Phone') }}</dt>
                        <dd class="col-7">{{ $employee->phone_number ?: '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Type') }}</dt>
                        <dd class="col-7"><x-enum-badge :value="$employee->employment_type" /></dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Joined') }}</dt>
                        <dd class="col-7">{{ $employee->join_date->format('j M Y') }}</dd>

                        @if ($employee->end_date)
                            <dt class="col-5 text-secondary fw-normal">{{ __('Last day') }}</dt>
                            <dd class="col-7">{{ $employee->end_date->format('j M Y') }}</dd>
                        @endif

                        <dt class="col-5 text-secondary fw-normal">{{ __('Date of birth') }}</dt>
                        <dd class="col-7">{{ $employee->date_of_birth?->format('j M Y') ?? '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Address') }}</dt>
                        <dd class="col-7">{{ $employee->address ?: '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Login') }}</dt>
                        <dd class="col-7 mb-0 text-break">
                            @if ($employee->user)
                                <a href="{{ route('admin.users.edit', $employee->user) }}">{{ $employee->user->email }}</a>
                            @else
                                <span class="text-secondary">{{ __('Not linked') }}</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card border-danger-subtle">
                <div class="card-header">
                    <h2 class="text-danger">{{ __('Delete employee') }}</h2>
                </div>
                <div class="card-body">
                    <p class="text-secondary small">{{ __('Removes this employee with their leave and attendance history. To keep the history, set the status to Resigned instead.') }}</p>
                    <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($employee->name), @js(route('admin.employees.destroy', $employee)))">
                        <i class="bi bi-trash me-1"></i>{{ __('Delete employee') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            @include('partials.leave-balances', ['balances' => $balances, 'year' => now()->year])

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2>{{ __('Recent leave') }}</h2>
                    <a href="{{ route('admin.leave_requests.index', ['search' => $employee->employee_no]) }}" class="small">{{ __('View all') }}</a>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Dates') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Days') }}</th>
                                <th class="pe-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leaveRequests as $leave)
                                <tr>
                                    <td class="ps-3 text-nowrap">
                                        <a href="{{ route('admin.leave_requests.show', $leave) }}" class="text-decoration-none">
                                            @include('partials.leave-dates', ['leave' => $leave])
                                        </a>
                                    </td>
                                    <td>{{ $leave->leaveType->name }}</td>
                                    <td>{{ $leave->days }}</td>
                                    <td class="pe-3"><x-enum-badge :value="$leave->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary py-4">{{ __('No leave requests yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Recent attendance') }}</h2>
                </div>
                @include('partials.attendance-table', ['attendances' => $attendances])
            </div>
        </div>

        <x-delete-modal :title="__('Delete employee?')" :button="__('Delete employee')">
            {{ __('This permanently deletes') }} <strong x-text="name"></strong> {{ __('with their leave and attendance records. This cannot be undone.') }}
        </x-delete-modal>
    </div>
</x-layouts.app>
