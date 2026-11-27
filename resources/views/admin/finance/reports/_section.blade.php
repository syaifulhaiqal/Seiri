{{-- Rows of account => amount (in sen) with a total line. --}}
<tr class="table-light">
    <th class="ps-3" colspan="2">{{ $title }}</th>
</tr>
@forelse ($rows as $row)
    <tr>
        <td class="ps-4">
            <a href="{{ route('admin.reports.general_ledger', ['account' => $row['account']->id] + ($range ?? [])) }}" class="text-decoration-none text-body">{{ $row['account']->displayName() }}</a>
        </td>
        <td class="text-end pe-3 text-nowrap">{{ \App\Support\Money::format(\App\Support\Money::fromCents($row['amount']), false) }}</td>
    </tr>
@empty
    <tr><td class="ps-4 text-secondary" colspan="2">{{ __('Nothing in this period.') }}</td></tr>
@endforelse
@foreach ($extra ?? [] as $label => $amount)
    <tr>
        <td class="ps-4 fst-italic">{{ $label }}</td>
        <td class="text-end pe-3 text-nowrap">{{ \App\Support\Money::format(\App\Support\Money::fromCents($amount), false) }}</td>
    </tr>
@endforeach
<tr class="fw-semibold">
    <td class="ps-3">{{ __('Total :section', ['section' => strtolower($title)]) }}</td>
    <td class="text-end pe-3 text-nowrap border-top">@money(\App\Support\Money::fromCents($total))</td>
</tr>
