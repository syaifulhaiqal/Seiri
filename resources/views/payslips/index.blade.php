<x-layouts.app :title="__('My payslips')">
    <div class="page-header">
        <div>
            <h1>{{ __('My payslips') }}</h1>
            <p class="text-secondary mb-0">{{ __('Payslips appear here once the month\'s payroll is approved.') }}</p>
        </div>
    </div>

    @if (! $employee)
        @include('partials.no-employee-record')
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">{{ __('Month') }}</th>
                            <th class="text-end">{{ __('Gross') }}</th>
                            <th class="text-end">{{ __('Deductions') }}</th>
                            <th class="text-end">{{ __('Net pay') }}</th>
                            <th class="text-end pe-3"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payslips as $payslip)
                            <tr>
                                <td class="ps-3 fw-medium">{{ $payslip->payrollRun->periodLabel() }}</td>
                                <td class="text-end text-nowrap">@money($payslip->gross_pay)</td>
                                <td class="text-end text-nowrap">@money($payslip->total_deductions)</td>
                                <td class="text-end text-nowrap fw-medium">@money($payslip->net_pay)</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('payslips.show', $payslip) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">
                                    <i class="bi bi-wallet2 fs-3 d-block mb-2"></i>
                                    {{ __('No payslips yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($payslips->hasPages())
                <div class="card-body border-top pb-0">
                    {{ $payslips->links() }}
                </div>
            @endif
        </div>
    @endif
</x-layouts.app>
