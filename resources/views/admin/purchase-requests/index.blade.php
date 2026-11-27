<x-layouts.app :title="__('Purchase requests')">
    <div class="page-header">
        <div>
            <h1>{{ __('Purchase requests') }}</h1>
            <p class="text-secondary mb-0">{{ trans_choice(':count request waiting for approval|:count requests waiting for approval', $pendingCount) }}</p>
        </div>
        @can('procurement.manage')
            <a href="{{ route('admin.purchase_requests.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('New request') }}</a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.purchase_requests.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search number or purpose') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\PurchaseRequestStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.purchase_requests.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Number') }}</th>
                        <th>{{ __('Purpose') }}</th>
                        <th>{{ __('Raised by') }}</th>
                        <th>{{ __('Needed by') }}</th>
                        <th class="text-end">{{ __('Estimate') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td class="ps-3 fw-medium text-nowrap">{{ $item->number }}</td>
                            <td>{{ str($item->purpose)->limit(70) }}</td>
                            <td>{{ $item->requester?->name ?? '—' }}</td>
                            <td class="text-nowrap">{{ $item->needed_by?->translatedFormat('j M Y') ?? '—' }}</td>
                            <td class="text-end text-nowrap">@money($item->total)</td>
                            <td><x-enum-badge :value="$item->status" /></td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="{{ route('admin.purchase_requests.show', $item) }}" @class(['btn btn-sm', 'btn-primary' => $item->isPending(), 'btn-outline-secondary' => ! $item->isPending()])>
                                    {{ $item->isPending() ? __('Review') : __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-cart fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('No purchase requests match your filters.') : __('No purchase requests yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($requests->hasPages())
            <div class="card-body border-top pb-0">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
