<x-layouts.app :title="__('Payroll')">
    <div class="page-header">
        <div>
            <h1>{{ __('Payroll') }}</h1>
            <p class="text-secondary mb-0">{{ __('Prepare a month, check the payslips, approve, then mark it paid.') }}</p>
        </div>
    </div>

    <div class="row g-4">
        @can('payroll.manage')
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Run payroll') }}</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.payroll.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                        @csrf
                        <x-form.input name="period" type="month" :label="__('Month')" :value="$nextPeriod" required />
                        <p class="small text-secondary">{{ __('Creates a draft payslip for every active employee from their salary, pay items and unpaid leave.') }}</p>
                        <x-submit-button>{{ __('Prepare payroll') }}</x-submit-button>
                    </form>
                </div>
            </div>
        </div>
        @endcan

        <div @class(['col-lg-8' => auth()->user()->can('payroll.manage'), 'col-12' => ! auth()->user()->can('payroll.manage')])>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Month') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Staff') }}</th>
                                <th class="text-end">{{ __('Gross') }}</th>
                                <th class="text-end">{{ __('Net pay') }}</th>
                                <th class="text-end pe-3">{{ __('Total cost') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($runs as $run)
                                <tr>
                                    <td class="ps-3"><a href="{{ route('admin.payroll.show', $run) }}" class="fw-medium text-decoration-none">{{ $run->periodLabel() }}</a></td>
                                    <td><x-enum-badge :value="$run->status" /></td>
                                    <td class="text-end">{{ $run->employee_count }}</td>
                                    <td class="text-end text-nowrap">@money($run->total_gross)</td>
                                    <td class="text-end text-nowrap">@money($run->total_net)</td>
                                    <td class="text-end text-nowrap pe-3">@money($run->totalCost())</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-5">
                                        <i class="bi bi-wallet2 fs-3 d-block mb-2"></i>
                                        {{ __('No payroll has been run yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($runs->hasPages())
                    <div class="card-body border-top pb-0">
                        {{ $runs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
