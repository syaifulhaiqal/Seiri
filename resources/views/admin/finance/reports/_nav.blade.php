{{-- Tabs between the four financial reports. --}}
<ul class="nav nav-pills gap-1 mb-4 d-print-none">
    @foreach ([
        'admin.reports.profit_and_loss' => __('Profit & loss'),
        'admin.reports.balance_sheet' => __('Balance sheet'),
        'admin.reports.trial_balance' => __('Trial balance'),
        'admin.reports.general_ledger' => __('General ledger'),
    ] as $name => $label)
        <li class="nav-item">
            <a @class(['nav-link', 'active' => request()->routeIs($name)]) href="{{ route($name) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
