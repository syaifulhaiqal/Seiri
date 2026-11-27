<x-layouts.app :title="__('General ledger')">
    @include('admin.finance.reports._header', [
        'heading' => $account ? $account->displayName() : __('General ledger'),
        'subheading' => __(':from to :to', ['from' => $from->format('j M Y'), 'to' => $to->format('j M Y')]),
        'accountOptions' => $accounts,
    ])

    @if (! $report)
        <div class="card">
            <div class="card-body text-center text-secondary py-5">{{ __('Add an account first.') }}</div>
        </div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">{{ __('Date') }}</th>
                            <th>{{ __('Entry') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-end">{{ __('Debit') }}</th>
                            <th class="text-end">{{ __('Credit') }}</th>
                            <th class="text-end pe-3">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td class="ps-3 small" colspan="5">{{ __('Opening balance') }}</td>
                            <td class="text-end pe-3 text-nowrap">{{ \App\Support\Money::format(\App\Support\Money::fromCents($report['opening']), false) }}</td>
                        </tr>
                        @forelse ($report['lines'] as $row)
                            @php($line = $row['line'])
                            <tr>
                                <td class="ps-3 small text-nowrap">{{ $line->entry->date->format('j M Y') }}</td>
                                <td class="small text-nowrap"><a href="{{ route('admin.journal_entries.show', $line->entry) }}" class="text-decoration-none">{{ $line->entry->number }}</a></td>
                                <td class="small">{{ $line->description ?: $line->entry->description }}</td>
                                <td class="text-end text-nowrap">{{ (float) $line->debit ? \App\Support\Money::format($line->debit, false) : '' }}</td>
                                <td class="text-end text-nowrap">{{ (float) $line->credit ? \App\Support\Money::format($line->credit, false) : '' }}</td>
                                <td class="text-end text-nowrap pe-3">{{ \App\Support\Money::format(\App\Support\Money::fromCents($row['balance']), false) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-secondary py-4">{{ __('No postings in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td class="ps-3" colspan="3">{{ __('Closing balance') }}</td>
                            <td class="text-end text-nowrap">{{ \App\Support\Money::format(\App\Support\Money::fromCents($report['debit']), false) }}</td>
                            <td class="text-end text-nowrap">{{ \App\Support\Money::format(\App\Support\Money::fromCents($report['credit']), false) }}</td>
                            <td class="text-end text-nowrap pe-3">@money(\App\Support\Money::fromCents($report['closing']))</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</x-layouts.app>
