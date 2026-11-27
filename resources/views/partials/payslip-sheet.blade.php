{{-- A printable payslip. Expects $run (PayrollRun) and $payslip (Payslip). --}}
@php
    $allowances = collect($payslip->items ?? [])->where('type', 'allowance');
    $deductions = collect($payslip->items ?? [])->where('type', 'deduction');
@endphp

<div class="card document-sheet">
    <div class="card-body p-4 p-md-5">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <div class="fs-4 fw-bold">{{ config('app.name') }}</div>
                <div class="text-secondary small">{{ __('Payslip') }}</div>
            </div>
            <div class="text-md-end">
                <div class="fs-5 fw-semibold">{{ $run->periodLabel() }}</div>
                <x-enum-badge :value="$run->status" class="d-print-none" />
            </div>
        </div>

        <div class="row small mb-4 g-2">
            <div class="col-sm-6">
                <div class="fw-semibold">{{ $payslip->employee_name }}</div>
                <div class="text-secondary">{{ $payslip->employee_no }}</div>
                <div class="text-secondary">{{ $payslip->job_title }}{{ $payslip->department_name ? ' · ' . $payslip->department_name : '' }}</div>
            </div>
            <div class="col-sm-6 text-sm-end">
                <div>{{ __('Working days: :days', ['days' => $payslip->working_days]) }}</div>
                <div>{{ __('Days paid: :days', ['days' => $payslip->days_worked - $payslip->unpaid_leave_days]) }}</div>
                @if ($payslip->bank_account_no)
                    <div class="text-secondary">{{ $payslip->bank_name }} {{ $payslip->bank_account_no }}</div>
                @endif
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr><th class="ps-0" colspan="2">{{ __('Earnings') }}</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-0">
                                {{ __('Basic salary') }}
                                @if ((float) $payslip->basic_pay !== (float) $payslip->basic_salary)
                                    <div class="small text-secondary">{{ __('Pro-rated from :amount', ['amount' => \App\Support\Money::format($payslip->basic_salary)]) }}</div>
                                @endif
                            </td>
                            <td class="text-end pe-0 text-nowrap">{{ \App\Support\Money::format($payslip->basic_pay, false) }}</td>
                        </tr>
                        @foreach ($allowances as $item)
                            <tr>
                                <td class="ps-0">{{ $item['name'] }}</td>
                                <td class="text-end pe-0 text-nowrap">{{ \App\Support\Money::format($item['amount'], false) }}</td>
                            </tr>
                        @endforeach
                        @if ((float) $payslip->unpaid_leave_deduction > 0)
                            <tr>
                                <td class="ps-0">{{ trans_choice('Unpaid leave (:count day)|Unpaid leave (:count days)', $payslip->unpaid_leave_days) }}</td>
                                <td class="text-end pe-0 text-nowrap">−{{ \App\Support\Money::format($payslip->unpaid_leave_deduction, false) }}</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td class="ps-0">{{ __('Gross pay') }}</td>
                            <td class="text-end pe-0 text-nowrap">@money($payslip->gross_pay)</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr><th class="ps-0" colspan="2">{{ __('Deductions') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            __('EPF (employee)') => $payslip->epf_employee,
                            __('SOCSO (employee)') => $payslip->socso_employee,
                            __('EIS (employee)') => $payslip->eis_employee,
                            __('PCB (income tax)') => $payslip->pcb,
                        ] as $label => $amount)
                            @continue((float) $amount === 0.0)
                            <tr>
                                <td class="ps-0">{{ $label }}</td>
                                <td class="text-end pe-0 text-nowrap">{{ \App\Support\Money::format($amount, false) }}</td>
                            </tr>
                        @endforeach
                        @foreach ($deductions as $item)
                            <tr>
                                <td class="ps-0">{{ $item['name'] }}</td>
                                <td class="text-end pe-0 text-nowrap">{{ \App\Support\Money::format($item['amount'], false) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td class="ps-0">{{ __('Total deductions') }}</td>
                            <td class="text-end pe-0 text-nowrap">@money($payslip->total_deductions)</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center border rounded-3 p-3 mt-4 bg-body-tertiary">
            <span class="fw-semibold">{{ __('Net pay') }}</span>
            <span class="fs-4 fw-bold">@money($payslip->net_pay)</span>
        </div>

        <div class="small text-secondary mt-3">
            {{ __('Employer contributions: EPF :epf · SOCSO :socso · EIS :eis', [
                'epf' => \App\Support\Money::format($payslip->epf_employer),
                'socso' => \App\Support\Money::format($payslip->socso_employer),
                'eis' => \App\Support\Money::format($payslip->eis_employer),
            ]) }}
        </div>
    </div>
</div>
