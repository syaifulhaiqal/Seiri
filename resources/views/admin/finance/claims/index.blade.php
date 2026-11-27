<x-layouts.app :title="__('Expense claims')">
    <div class="page-header">
        <div>
            <h1>{{ __('Expense claims') }}</h1>
            <p class="text-secondary mb-0">{{ __('Staff spending to approve and pay back.') }}</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.claims.index', ['status' => 'pending']) }}" class="card stat-card h-100 text-decoration-none text-body">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon badge-soft-warning"><i class="bi bi-hourglass-split"></i></span>
                    <div>
                        <div class="text-secondary small">{{ __('To review') }}</div>
                        <div class="stat-value">{{ number_format($pending) }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.claims.index', ['status' => 'approved']) }}" class="card stat-card h-100 text-decoration-none text-body">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon badge-soft-primary"><i class="bi bi-cash-stack"></i></span>
                    <div>
                        <div class="text-secondary small">{{ __('Approved, not paid back') }}</div>
                        <div class="stat-value">@money($toPay)</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.claims.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search number, description or employee') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\ClaimStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-5 d-flex gap-2 justify-content-lg-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.claims.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Claim') }}</th>
                        <th>{{ __('Employee') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Spent on') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($claims as $claim)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.claims.show', $claim) }}" class="fw-medium text-decoration-none">{{ $claim->number }}</a>
                                <div class="small text-secondary">{{ str($claim->description)->limit(50) }}</div>
                            </td>
                            <td>{{ $claim->employee->name }}</td>
                            <td class="small">{{ $claim->account->name }}</td>
                            <td class="small text-nowrap">{{ $claim->date->translatedFormat('j M Y') }}</td>
                            <td><x-enum-badge :value="$claim->status" /></td>
                            <td class="text-end text-nowrap">@money($claim->amount)</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.claims.show', $claim) }}" @class(['btn btn-sm', 'btn-primary' => $claim->isPending(), 'btn-outline-secondary' => ! $claim->isPending()])>
                                    {{ $claim->isPending() ? __('Review') : __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('No claims match your filters.') : __('No expense claims yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($claims->hasPages())
            <div class="card-body border-top pb-0">
                {{ $claims->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
