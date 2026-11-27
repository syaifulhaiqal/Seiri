{{-- A printable yearly statement of remuneration in the layout of Borang EA. Expects $statement from App\Services\EaStatement. --}}
@php
    $employee = $statement['employee'];
    $money = fn ($amount) => \App\Support\Money::format($amount, false);
    $socsoTotal = \App\Support\Money::fromCents(\App\Support\Money::toCents($statement['socso_employee']) + \App\Support\Money::toCents($statement['eis_employee']));
@endphp

<div class="card document-sheet">
    <div class="card-body p-4 p-md-5">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <div class="fs-4 fw-bold">{{ config('payroll.employer.name') }}</div>
                @if (config('payroll.employer.address'))
                    <div class="text-secondary small">{{ config('payroll.employer.address') }}</div>
                @endif
                @if (config('payroll.employer.tax_no'))
                    <div class="text-secondary small">{{ __('Employer no.: :number', ['number' => config('payroll.employer.tax_no')]) }}</div>
                @endif
            </div>
            <div class="text-md-end">
                <div class="fs-5 fw-semibold">{{ __('EA form :year', ['year' => $statement['year']]) }}</div>
                <div class="text-secondary small">{{ __('Statement of remuneration from employment for the year ended 31 December :year', ['year' => $statement['year']]) }}</div>
            </div>
        </div>

        <h3 class="h6 text-uppercase text-secondary">{{ __('A. Employee') }}</h3>
        <dl class="row small mb-4">
            <dt class="col-sm-4 fw-normal text-secondary">{{ __('Name') }}</dt>
            <dd class="col-sm-8 fw-semibold">{{ $employee->name }}</dd>
            <dt class="col-sm-4 fw-normal text-secondary">{{ __('Job title') }}</dt>
            <dd class="col-sm-8">{{ $employee->job_title }}</dd>
            <dt class="col-sm-4 fw-normal text-secondary">{{ __('Employee no.') }}</dt>
            <dd class="col-sm-8">{{ $employee->employee_no }}</dd>
            <dt class="col-sm-4 fw-normal text-secondary">{{ __('IC / passport no.') }}</dt>
            <dd class="col-sm-8">{{ $employee->ic_no ?: '—' }}</dd>
            <dt class="col-sm-4 fw-normal text-secondary">{{ __('Income tax no.') }}</dt>
            <dd class="col-sm-8">{{ $employee->tax_no ?: '—' }}</dd>
            <dt class="col-sm-4 fw-normal text-secondary">{{ __('Months paid') }}</dt>
            <dd class="col-sm-8 mb-0">{{ $statement['months'] }}</dd>
        </dl>

        <h3 class="h6 text-uppercase text-secondary">{{ __('B. Employment income') }}</h3>
        <table class="table table-sm mb-4">
            <tbody>
                <tr>
                    <td class="ps-0">{{ __('Salary and wages') }}</td>
                    <td class="text-end pe-0 text-nowrap">{{ $money($statement['salary']) }}</td>
                </tr>
                @if ((float) $statement['unpaid_leave'] > 0)
                    <tr>
                        <td class="ps-0">{{ __('Less unpaid leave') }}</td>
                        <td class="text-end pe-0 text-nowrap">−{{ $money($statement['unpaid_leave']) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="ps-0">{{ __('Overtime') }}</td>
                    <td class="text-end pe-0 text-nowrap">{{ $money($statement['overtime']) }}</td>
                </tr>
                <tr>
                    <td class="ps-0">{{ __('Allowances') }}</td>
                    <td class="text-end pe-0 text-nowrap">{{ $money($statement['allowances']) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="fw-semibold">
                    <td class="ps-0">{{ __('Total gross remuneration') }}</td>
                    <td class="text-end pe-0 text-nowrap">@money($statement['gross'])</td>
                </tr>
            </tfoot>
        </table>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <h3 class="h6 text-uppercase text-secondary">{{ __('D. Tax deducted') }}</h3>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="ps-0">{{ __('Monthly tax deductions (PCB)') }}</td>
                            <td class="text-end pe-0 text-nowrap">{{ $money($statement['pcb']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h3 class="h6 text-uppercase text-secondary">{{ __('E. Employee contributions') }}</h3>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="ps-0">{{ __('EPF') }}</td>
                            <td class="text-end pe-0 text-nowrap">{{ $money($statement['epf_employee']) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-0">{{ __('SOCSO and EIS') }}</td>
                            <td class="text-end pe-0 text-nowrap">{{ $money($socsoTotal) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h3 class="h6 text-uppercase text-secondary">{{ __('By month') }}</h3>
        <div class="table-responsive">
            <table class="table table-sm small mb-4">
                <thead>
                    <tr>
                        <th class="ps-0">{{ __('Month') }}</th>
                        <th class="text-end">{{ __('Gross') }}</th>
                        <th class="text-end">{{ __('EPF') }}</th>
                        <th class="text-end">{{ __('SOCSO') }}</th>
                        <th class="text-end">{{ __('EIS') }}</th>
                        <th class="text-end pe-0">{{ __('PCB') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($statement['payslips'] as $payslip)
                        <tr>
                            <td class="ps-0">{{ $payslip->payrollRun->periodLabel() }}</td>
                            <td class="text-end">{{ $money($payslip->gross_pay) }}</td>
                            <td class="text-end">{{ $money($payslip->epf_employee) }}</td>
                            <td class="text-end">{{ $money($payslip->socso_employee) }}</td>
                            <td class="text-end">{{ $money($payslip->eis_employee) }}</td>
                            <td class="text-end pe-0">{{ $money($payslip->pcb) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="small text-secondary mb-0">
            {{ __('Employer contributions (not part of income): EPF :epf, SOCSO :socso, EIS :eis.', [
                'epf' => \App\Support\Money::format($statement['epf_employer']),
                'socso' => \App\Support\Money::format($statement['socso_employer']),
                'eis' => \App\Support\Money::format($statement['eis_employer']),
            ]) }}
            {{ __('Figures come from approved payroll. Use them to fill in the official EA form and your e-Filing return.') }}
        </p>
    </div>
</div>
