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
        @can('employees.manage')
            <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i>{{ __('Edit employee') }}
            </a>
        @endcan
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

                        <dt class="col-5 text-secondary fw-normal">{{ __('Clock in') }}</dt>
                        <dd class="col-7">{{ $employee->can_work_remotely ? __('Office QR or remote') : __('Office QR only') }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Joined') }}</dt>
                        <dd class="col-7">{{ $employee->join_date->translatedFormat('j M Y') }}</dd>

                        @if ($employee->end_date)
                            <dt class="col-5 text-secondary fw-normal">{{ __('Last day') }}</dt>
                            <dd class="col-7">{{ $employee->end_date->translatedFormat('j M Y') }}</dd>
                        @endif

                        <dt class="col-5 text-secondary fw-normal">{{ __('Date of birth') }}</dt>
                        <dd class="col-7">{{ $employee->date_of_birth?->translatedFormat('j M Y') ?? '—' }}</dd>

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

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2>{{ __('Approvers') }}</h2>
                    @can('employees.manage')
                        <a href="{{ route('admin.employees.approvers.edit', $employee) }}" class="btn btn-sm btn-outline-secondary">{{ __('Edit') }}</a>
                    @endcan
                </div>
                <div class="card-body small">
                    @foreach (\App\Enums\ApprovalType::cases() as $type)
                        @php
                            $chain = $employee->approvers->where('type', $type);
                        @endphp
                        <div @class(['mb-2' => ! $loop->last])>
                            <span class="text-secondary">{{ $type->label() }}:</span>
                            {{ $chain->isEmpty() ? __('HR / finance decides') : $chain->map(fn ($approver) => $approver->approver->name)->join(' → ') }}
                        </div>
                    @endforeach
                </div>
            </div>

            @can('payroll.view')
            <div class="card mb-4">
                <div class="card-header">
                    <h2>{{ __('Pay') }}</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-secondary fw-normal">{{ __('Basic salary') }}</dt>
                        <dd class="col-7">{{ (float) $employee->basic_salary > 0 ? \App\Support\Money::format($employee->basic_salary) : __('Not set') }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Monthly PCB') }}</dt>
                        <dd class="col-7">@money($employee->monthly_pcb)</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Income tax no.') }}</dt>
                        <dd class="col-7">{{ $employee->tax_no ?: '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">{{ __('Bank') }}</dt>
                        <dd class="col-7 mb-0">{{ $employee->bank_account_no ? $employee->bank_name . ' ' . $employee->bank_account_no : '—' }}</dd>
                    </dl>
                </div>
                <ul class="list-group list-group-flush border-top">
                    @forelse ($employee->payItems as $item)
                        <li class="list-group-item d-flex align-items-center justify-content-between gap-2">
                            <span class="small">
                                {{ $item->name }}
                                <x-enum-badge :value="$item->type" class="ms-1" />
                            </span>
                            <span class="d-flex align-items-center gap-2 text-nowrap">
                                <span class="small">{{ $item->type === \App\Enums\PayItemType::Deduction ? '−' : '+' }}@money($item->amount)</span>
                                @can('payroll.manage')
                                    <form method="POST" action="{{ route('admin.employees.pay_items.destroy', [$employee, $item]) }}" x-data="formSubmit" x-on:submit="submit()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-secondary p-0" x-bind:disabled="submitting" title="{{ __('Remove') }}">
                                            <i class="bi bi-x-lg"></i><span class="visually-hidden">{{ __('Remove :name', ['name' => $item->name]) }}</span>
                                        </button>
                                    </form>
                                @endcan
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item small text-secondary">{{ __('No monthly allowances or deductions.') }}</li>
                    @endforelse
                </ul>
                @can('payroll.manage')
                <div class="card-body border-top">
                    <form method="POST" action="{{ route('admin.employees.pay_items.store', $employee) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                        @csrf
                        <div class="small fw-medium mb-2">{{ __('Add a monthly item') }}</div>
                        <x-form.input name="name" bag="payItem" :placeholder="__('e.g. Transport allowance')" aria-label="{{ __('Name') }}" />
                        <div class="row g-2">
                            <div class="col-6">
                                <x-form.select name="type" bag="payItem" :options="\App\Enums\PayItemType::options()" aria-label="{{ __('Type') }}" />
                            </div>
                            <div class="col-6">
                                <x-form.input name="amount" type="number" step="0.01" min="0.01" bag="payItem" :placeholder="__('Amount')" aria-label="{{ __('Amount') }}" />
                            </div>
                        </div>
                        <x-submit-button variant="outline-primary" class="btn-sm">{{ __('Add item') }}</x-submit-button>
                    </form>
                </div>
                @endcan
            </div>
            @endcan

            @can('employees.manage')
            <div class="card border-danger-subtle">
                <div class="card-header">
                    <h2 class="text-danger">{{ __('Delete employee') }}</h2>
                </div>
                <div class="card-body">
                    <p class="text-secondary small">{{ __('Removes this employee with their leave and attendance history. Employees with payslips or expense claims cannot be deleted; set the status to Resigned instead.') }}</p>
                    <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($employee->name), @js(route('admin.employees.destroy', $employee)))">
                        <i class="bi bi-trash me-1"></i>{{ __('Delete employee') }}
                    </button>
                </div>
            </div>
            @endcan
        </div>

        <div class="col-lg-8">
            @include('partials.leave-balances', ['balances' => $balances, 'year' => now()->year])

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2>{{ __('Recent leave') }}</h2>
                    @can('leave.review')
                        <a href="{{ route('admin.leave_requests.index', ['search' => $employee->employee_no]) }}" class="small">{{ __('View all') }}</a>
                    @endcan
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
                                        @can('leave.review')
                                            <a href="{{ route('admin.leave_requests.show', $leave) }}" class="text-decoration-none">
                                                @include('partials.leave-dates', ['leave' => $leave])
                                            </a>
                                        @else
                                            @include('partials.leave-dates', ['leave' => $leave])
                                        @endcan
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

            <div class="card mb-4">
                <div class="card-header">
                    <h2>{{ __('Recent attendance') }}</h2>
                </div>
                @include('partials.attendance-table', ['attendances' => $attendances])
            </div>

            @can('payroll.view')
            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Recent payslips') }}</h2>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Month') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Gross') }}</th>
                                <th class="text-end pe-3">{{ __('Net pay') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payslips as $payslip)
                                <tr>
                                    <td class="ps-3"><a href="{{ route('admin.payroll.payslip', [$payslip->payrollRun, $payslip]) }}" class="text-decoration-none">{{ $payslip->payrollRun->periodLabel() }}</a></td>
                                    <td><x-enum-badge :value="$payslip->payrollRun->status" /></td>
                                    <td class="text-end text-nowrap">@money($payslip->gross_pay)</td>
                                    <td class="text-end text-nowrap pe-3">@money($payslip->net_pay)</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary py-4">{{ __('No payslips yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endcan

            @can('training.manage')
            <div class="card mt-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2>{{ __('Training') }}</h2>
                    <a href="{{ route('admin.trainings.create', ['employee' => $employee->id]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('Add training') }}</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($trainings as $training)
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
                            <a href="{{ route('admin.trainings.edit', $training) }}" class="text-decoration-none min-w-0">
                                <span class="fw-medium">{{ $training->title }}</span>
                                <span class="small text-secondary d-block">{{ \App\Support\DateRange::format($training->start_date, $training->end_date) }}</span>
                            </a>
                            <x-enum-badge :value="$training->status" />
                        </li>
                    @empty
                        <li class="list-group-item text-center text-secondary py-4">{{ __('No training recorded yet.') }}</li>
                    @endforelse
                </ul>
            </div>
            @endcan
        </div>

        <x-delete-modal :title="__('Delete employee?')" :button="__('Delete employee')">
            {{ __('This permanently deletes') }} <strong x-text="name"></strong> {{ __('with their leave and attendance records. This cannot be undone.') }}
        </x-delete-modal>
    </div>
</x-layouts.app>
