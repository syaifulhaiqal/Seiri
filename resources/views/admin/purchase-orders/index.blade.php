<x-layouts.app :title="__('Purchase orders')">
    <div class="page-header">
        <div>
            <h1>{{ __('Purchase orders') }}</h1>
            <p class="text-secondary mb-0">{{ trans_choice(':count order waiting for delivery|:count orders waiting for delivery', $openCount) }}</p>
        </div>
        <a href="{{ route('admin.purchase_orders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('New order') }}</a>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.purchase_orders.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search number or supplier') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select" aria-label="{{ __('Status') }}" x-on:change="submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Enums\PurchaseOrderStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.purchase_orders.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Number') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Order date') }}</th>
                        <th>{{ __('Expected') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="ps-3 fw-medium text-nowrap">{{ $order->number }}</td>
                            <td>{{ $order->contact->name }}</td>
                            <td class="text-nowrap">{{ $order->order_date->translatedFormat('j M Y') }}</td>
                            <td class="text-nowrap">{{ $order->expected_date?->translatedFormat('j M Y') ?? '—' }}</td>
                            <td class="text-end text-nowrap">@money($order->total)</td>
                            <td>
                                <x-enum-badge :value="$order->status" />
                                @if ($order->bill)
                                    <div class="small text-secondary mt-1">{{ __('Billed: :number', ['number' => $order->bill->number]) }}</div>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.purchase_orders.show', $order) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('View') }}">
                                    <i class="bi bi-eye"></i><span class="visually-hidden">{{ __('View') }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-truck fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('No purchase orders match your filters.') : __('No purchase orders yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="card-body border-top pb-0">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
