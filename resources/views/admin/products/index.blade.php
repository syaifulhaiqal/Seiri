<x-layouts.app :title="__('Products & stock')">
    <div class="page-header">
        <div>
            <h1>{{ __('Products & stock') }}</h1>
            <p class="text-secondary mb-0">
                {{ trans_choice(':count active product|:count active products', $totals['count']) }}
                · {{ __('Stock value at cost: :amount', ['amount' => \App\Support\Money::format($totals['value'])]) }}
                @if ($totals['low'] > 0)
                    · <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="text-warning-emphasis">{{ trans_choice(':count low on stock|:count low on stock', $totals['low']) }}</a>
                @endif
            </p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('Add product') }}</a>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 align-items-center" x-data="autoSubmit">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-body"><i class="bi bi-search text-secondary"></i></span>
                        <input type="search" name="search" class="form-control border-start-0 ps-0" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search SKU or name') }}" aria-label="{{ __('Search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="stock" class="form-select" aria-label="{{ __('Show') }}" x-on:change="submit()">
                        <option value="">{{ __('Active products') }}</option>
                        <option value="low" @selected(($filters['stock'] ?? '') === 'low')>{{ __('Low on stock') }}</option>
                        <option value="inactive" @selected(($filters['stock'] ?? '') === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Product') }}</th>
                        <th class="text-end">{{ __('On hand') }}</th>
                        <th class="text-end">{{ __('Cost') }}</th>
                        <th class="text-end">{{ __('Price') }}</th>
                        <th class="text-end">{{ __('Value') }}</th>
                        <th class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.products.show', $product) }}" class="fw-medium text-decoration-none">{{ $product->name }}</a>
                                <div class="small text-secondary">{{ $product->sku }}</div>
                            </td>
                            <td class="text-end text-nowrap">
                                {{ $product->onHand() }} {{ $product->unit }}
                                @if ($product->isLow())
                                    <span class="badge rounded-pill badge-soft badge-soft-warning ms-1">{{ __('Low') }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">{{ \App\Support\Money::format($product->cost_price, false) }}</td>
                            <td class="text-end text-nowrap">{{ \App\Support\Money::format($product->sale_price, false) }}</td>
                            <td class="text-end text-nowrap">@money(\App\Support\Money::fromCents(max(0, $product->stockValueCents())))</td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                    <i class="bi bi-pencil"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">
                                <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
                                {{ array_filter($filters) ? __('No products match your filters.') : __('No products yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
