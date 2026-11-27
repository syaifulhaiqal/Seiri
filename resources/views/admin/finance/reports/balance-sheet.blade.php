@php
    $balanced = $report['total_assets'] === $report['total_liabilities'] + $report['total_equity'];
@endphp

<x-layouts.app :title="__('Balance sheet')">
    @include('admin.finance.reports._header', [
        'heading' => __('Balance sheet'),
        'subheading' => __('As of :date', ['date' => $asOf->translatedFormat('j M Y')]),
    ])

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            @include('admin.finance.reports._section', ['title' => __('Assets'), 'rows' => $report['assets'], 'total' => $report['total_assets']])
                            @include('admin.finance.reports._section', ['title' => __('Liabilities'), 'rows' => $report['liabilities'], 'total' => $report['total_liabilities']])
                            @include('admin.finance.reports._section', [
                                'title' => __('Equity'),
                                'rows' => $report['equity'],
                                'total' => $report['total_equity'],
                                'extra' => [__('Profit to date, not yet closed') => $report['earnings']],
                            ])
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td class="ps-3 py-3">{{ __('Liabilities + equity') }}</td>
                                <td class="text-end pe-3 py-3 text-nowrap">@money(\App\Support\Money::fromCents($report['total_liabilities'] + $report['total_equity']))</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div @class(['card-body border-top small', 'text-success' => $balanced, 'text-danger' => ! $balanced])>
                    <i @class(['bi me-1', 'bi-check-circle' => $balanced, 'bi-exclamation-triangle' => ! $balanced])></i>
                    {{ $balanced ? __('Assets equal liabilities plus equity.') : __('The sheet does not balance. Check the trial balance.') }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
