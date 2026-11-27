<x-layouts.app :title="__('Trial balance')">
    @include('admin.finance.reports._header', [
        'heading' => __('Trial balance'),
        'subheading' => __('As of :date', ['date' => $asOf->translatedFormat('j M Y')]),
    ])

    <div class="row">
        <div class="col-xl-9">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Account') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th class="text-end">{{ __('Debit') }}</th>
                                <th class="text-end pe-3">{{ __('Credit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['rows'] as $row)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('admin.reports.general_ledger', ['account' => $row['account']->id, 'to' => $asOf->toDateString()]) }}" class="text-decoration-none text-body">{{ $row['account']->displayName() }}</a>
                                    </td>
                                    <td class="small text-secondary">{{ $row['account']->type->label() }}</td>
                                    <td class="text-end text-nowrap">{{ $row['debit'] ? \App\Support\Money::format(\App\Support\Money::fromCents($row['debit']), false) : '' }}</td>
                                    <td class="text-end text-nowrap pe-3">{{ $row['credit'] ? \App\Support\Money::format(\App\Support\Money::fromCents($row['credit']), false) : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary py-5">{{ __('Nothing has been posted yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td class="ps-3" colspan="2">
                                    {{ __('Total') }}
                                    @if ($report['debit'] === $report['credit'])
                                        <span class="badge rounded-pill badge-soft badge-soft-success ms-1">{{ __('Balanced') }}</span>
                                    @else
                                        <span class="badge rounded-pill badge-soft badge-soft-danger ms-1">{{ __('Out of balance') }}</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">@money(\App\Support\Money::fromCents($report['debit']))</td>
                                <td class="text-end text-nowrap pe-3">@money(\App\Support\Money::fromCents($report['credit']))</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
