<x-layouts.app :title="__('Chart of accounts')">
    <div class="page-header">
        <div>
            <h1>{{ __('Chart of accounts') }}</h1>
            <p class="text-secondary mb-0">{{ __('Every amount the app posts lands in one of these accounts.') }}</p>
        </div>
        <a href="{{ route('admin.accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Add account') }}
        </a>
    </div>

    <div x-data="confirmDelete">
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.accounts.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                            <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search code or name') }}" aria-label="{{ __('Search') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="type" class="form-select" aria-label="{{ __('Type') }}" x-on:change="submit()">
                            <option value="">{{ __('All types') }}</option>
                            @foreach (\App\Enums\AccountType::options() as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-5 d-flex gap-2 justify-content-lg-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                        @if (array_filter($filters))
                            <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @foreach (\App\Enums\AccountType::cases() as $type)
            @continue(! $groups->has($type->value))
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2>{{ $type->label() }}</h2>
                    <span class="small text-secondary">{{ $type->isDebitNormal() ? __('Debit balance') : __('Credit balance') }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3" style="width: 6rem">{{ __('Code') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th class="text-end">{{ __('Balance') }}</th>
                                <th class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groups[$type->value] as $account)
                                <tr @class(['text-secondary' => ! $account->is_active])>
                                    <td class="ps-3 font-monospace small">{{ $account->code }}</td>
                                    <td>
                                        <span class="fw-medium">{{ $account->name }}</span>
                                        @if ($account->isSystem())
                                            <span class="badge rounded-pill badge-soft badge-soft-secondary ms-1" title="{{ __('The app posts to this account automatically.') }}">{{ __('System') }}</span>
                                        @endif
                                        @unless ($account->is_active)
                                            <span class="badge rounded-pill badge-soft badge-soft-warning ms-1">{{ __('Off') }}</span>
                                        @endunless
                                        @if ($account->description)
                                            <div class="small text-secondary">{{ $account->description }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.reports.general_ledger', ['account' => $account->id]) }}" class="text-decoration-none text-body">
                                            @money(\App\Support\Money::fromCents($balances[$account->id] ?? 0))
                                        </a>
                                    </td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <a href="{{ route('admin.accounts.edit', $account) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                        </a>
                                        @if (! $account->isSystem() && $account->lines_count === 0)
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}" x-on:click="open(@js($account->displayName()), @js(route('admin.accounts.destroy', $account)))">
                                                <i class="bi bi-trash"></i><span class="visually-hidden">{{ __('Delete') }}</span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        @if ($groups->isEmpty())
            <div class="card">
                <div class="card-body text-center text-secondary py-5">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    {{ array_filter($filters) ? __('No accounts match your filters.') : __('No accounts yet.') }}
                </div>
            </div>
        @endif

        <x-delete-modal :title="__('Delete account?')" :button="__('Delete account')">
            {{ __('This permanently deletes') }} <strong x-text="name"></strong>.
        </x-delete-modal>
    </div>
</x-layouts.app>
