@php
    $range = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
@endphp

<x-layouts.app :title="__('Profit & loss')">
    @include('admin.finance.reports._header', [
        'heading' => __('Profit & loss'),
        'subheading' => __(':from to :to', ['from' => $from->translatedFormat('j M Y'), 'to' => $to->translatedFormat('j M Y')]),
    ])

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            @include('admin.finance.reports._section', ['title' => __('Income'), 'rows' => $report['income'], 'total' => $report['total_income']])
                            @include('admin.finance.reports._section', ['title' => __('Expenses'), 'rows' => $report['expenses'], 'total' => $report['total_expenses']])
                        </tbody>
                        <tfoot>
                            <tr @class(['fw-bold fs-6', 'text-success' => $report['net'] >= 0, 'text-danger' => $report['net'] < 0])>
                                <td class="ps-3 py-3">{{ $report['net'] >= 0 ? __('Net profit') : __('Net loss') }}</td>
                                <td class="text-end pe-3 py-3 text-nowrap">@money(\App\Support\Money::fromCents($report['net']))</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
