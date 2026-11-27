@php
    $status = $run->status;
@endphp

<x-layouts.app :title="__('Payroll :month', ['month' => $run->periodLabel()])">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.payroll.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to payroll') }}
            </a>
            <h1 class="mt-2">{{ __('Payroll :month', ['month' => $run->periodLabel()]) }}</h1>
            <p class="text-secondary mb-0">
                <x-enum-badge :value="$status" />
                · {{ trans_choice(':count payslip|:count payslips', $run->employee_count) }}
                @if ($run->approved_at)
                    · {{ __('Approved by :name on :date', ['name' => $run->approver?->name ?? __('Unknown'), 'date' => $run->approved_at->format('j M Y')]) }}
                @endif
                @if ($run->paid_at)
                    · {{ __('Paid :date', ['date' => $run->paid_at->format('j M Y')]) }}
                @endif
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2" x-data="confirmDelete">
            @if ($run->isDraft())
                <form method="POST" action="{{ route('admin.payroll.recalculate', $run) }}" x-data="formSubmit" x-on:submit="submit()">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary" x-bind:disabled="submitting">
                        <i class="bi bi-arrow-repeat me-1"></i>{{ __('Recalculate') }}
                    </button>
                </form>
                <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($run->periodLabel()), @js(route('admin.payroll.destroy', $run)))">
                    <i class="bi bi-trash me-1"></i>{{ __('Delete draft') }}
                </button>
                @if ($run->employee_count > 0)
                    <form method="POST" action="{{ route('admin.payroll.approve', $run) }}" x-data="formSubmit" x-on:submit="if (! confirm(@js(__('Approve payroll for :month? Payslips are locked and the cost is posted to the ledger.', ['month' => $run->periodLabel()])))) { $event.preventDefault() } else { submit() }">
                        @csrf
                        <button type="submit" class="btn btn-primary" x-bind:disabled="submitting">
                            <i class="bi bi-check2-circle me-1"></i>{{ __('Approve payroll') }}
                        </button>
                    </form>
                @endif
            @endif

            <x-delete-modal :title="__('Delete draft payroll?')" :button="__('Delete draft')">
                {{ __('This deletes the draft payslips for') }} <strong x-text="name"></strong>. {{ __('You can prepare it again.') }}
            </x-delete-modal>
        </div>
    </div>

    @if ($missingSalary->isNotEmpty())
        <div class="alert alert-warning d-flex gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                {{ __('These active employees have no basic salary, so they are left out:') }}
                @foreach ($missingSalary as $employee)
                    <a href="{{ route('admin.employees.edit', $employee) }}">{{ $employee->name }}</a>@if (! $loop->last), @endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($errors->payment->any())
        <div class="alert alert-danger d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ $errors->payment->first() }}</div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => __('Gross pay'), 'value' => $run->total_gross, 'icon' => 'bi-cash', 'color' => 'primary'],
            ['label' => __('Employee deductions'), 'value' => $run->total_deductions, 'icon' => 'bi-dash-circle', 'color' => 'warning'],
            ['label' => __('Net pay to staff'), 'value' => $run->total_net, 'icon' => 'bi-bank', 'color' => 'success'],
            ['label' => __('Employer contributions'), 'value' => $run->total_employer_contributions, 'icon' => 'bi-building', 'color' => 'info'],
        ] as $card)
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="stat-icon badge-soft-{{ $card['color'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                        <div>
                            <div class="text-secondary small">{{ $card['label'] }}</div>
                            <div class="stat-value fs-5">@money($card['value'])</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div @class(['col-12' => $status !== \App\Enums\PayrollStatus::Approved, 'col-lg-8' => $status === \App\Enums\PayrollStatus::Approved])>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Employee') }}</th>
                                <th class="text-end">{{ __('Days paid') }}</th>
                                <th class="text-end">{{ __('Gross') }}</th>
                                <th class="text-end">{{ __('EPF') }}</th>
                                <th class="text-end">{{ __('SOCSO + EIS') }}</th>
                                <th class="text-end">{{ __('PCB') }}</th>
                                <th class="text-end">{{ __('Other') }}</th>
                                <th class="text-end pe-3">{{ __('Net pay') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payslips as $payslip)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('admin.payroll.payslip', [$run, $payslip]) }}" class="fw-medium text-decoration-none">{{ $payslip->employee_name }}</a>
                                        <div class="small text-secondary">{{ $payslip->employee_no }}{{ $payslip->department_name ? ' · ' . $payslip->department_name : '' }}</div>
                                    </td>
                                    <td class="text-end">
                                        {{ $payslip->days_worked - $payslip->unpaid_leave_days }}/{{ $payslip->working_days }}
                                    </td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Money::format($payslip->gross_pay, false) }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Money::format($payslip->epf_employee, false) }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Money::format((float) $payslip->socso_employee + (float) $payslip->eis_employee, false) }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Money::format($payslip->pcb, false) }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Money::format($payslip->other_deductions, false) }}</td>
                                    <td class="text-end text-nowrap pe-3 fw-medium">{{ \App\Support\Money::format($payslip->net_pay, false) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-secondary py-5">{{ __('No active employees with a salary for this month.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($status === \App\Enums\PayrollStatus::Approved)
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('Pay salaries') }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="small text-secondary">{{ __('After the bank transfer goes out, record it here. EPF, SOCSO, EIS and PCB stay as payables until you pay them.') }}</p>
                        <form method="POST" action="{{ route('admin.payroll.pay', $run) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                            @csrf
                            <x-form.select name="account_id" bag="payment" :label="__('Paid from')" :options="$bankAccounts" :value="$bankAccounts->keys()->first()" required />
                            <x-form.input name="date" type="date" bag="payment" :label="__('Date paid')" :value="today()->toDateString()" required />
                            <x-submit-button>{{ __('Mark :amount as paid', ['amount' => \App\Support\Money::format($run->total_net)]) }}</x-submit-button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($run->journalEntry || $run->paymentEntry)
        <p class="small text-secondary mt-4 mb-0">
            {{ __('Ledger:') }}
            @if ($run->journalEntry)
                <a href="{{ route('admin.journal_entries.show', $run->journalEntry) }}">{{ $run->journalEntry->number }}</a> ({{ __('payroll cost') }})
            @endif
            @if ($run->paymentEntry)
                · <a href="{{ route('admin.journal_entries.show', $run->paymentEntry) }}">{{ $run->paymentEntry->number }}</a> ({{ __('salaries paid') }})
            @endif
        </p>
    @endif
</x-layouts.app>
