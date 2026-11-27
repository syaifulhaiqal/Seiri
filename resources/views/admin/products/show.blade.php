<x-layouts.app :title="$product->name">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.products.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to products') }}
            </a>
            <h1 class="mt-2">{{ $product->name }}</h1>
            <p class="text-secondary mb-0">
                {{ $product->sku }}
                @unless ($product->is_active)
                    · <span class="badge rounded-pill badge-soft badge-soft-secondary">{{ __('Inactive') }}</span>
                @endunless
            </p>
        </div>
        <div class="d-flex gap-2" x-data="confirmDelete">
            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>
            @if ($movements->isEmpty())
                <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($product->name), @js(route('admin.products.destroy', $product)))">
                    <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                </button>
            @endif

            <x-delete-modal :title="__('Delete product?')" :button="__('Delete product')">
                {{ __('This permanently deletes') }} <strong x-text="name"></strong>.
            </x-delete-modal>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-secondary small">{{ __('On hand') }}</div>
                    <div class="stat-value">{{ $product->onHand() }} {{ $product->unit }}</div>
                    @if ($product->isLow())
                        <span class="badge rounded-pill badge-soft badge-soft-warning">{{ __('At or below the reorder level of :count', ['count' => (float) $product->reorder_level]) }}</span>
                    @endif
                    <dl class="row small mb-0 mt-3">
                        <dt class="col-6 text-secondary fw-normal">{{ __('Cost price') }}</dt>
                        <dd class="col-6 text-end">@money($product->cost_price)</dd>
                        <dt class="col-6 text-secondary fw-normal">{{ __('Sale price') }}</dt>
                        <dd class="col-6 text-end">@money($product->sale_price)</dd>
                        <dt class="col-6 text-secondary fw-normal">{{ __('Stock value') }}</dt>
                        <dd class="col-6 text-end mb-0">@money(\App\Support\Money::fromCents(max(0, $product->stockValueCents())))</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Adjust stock') }}</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.products.adjust', $product) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                        @csrf
                        <x-form.select name="direction" bag="stock" :label="__('Change')" :options="['out' => __('Take out (used, sold, damaged)'), 'in' => __('Add (stock count, return)')]" value="out" required />
                        <div class="row">
                            <div class="col-6">
                                <x-form.input name="quantity" type="number" step="0.01" min="0" bag="stock" :label="__('Quantity')" required />
                            </div>
                            <div class="col-6">
                                <x-form.input name="date" type="date" bag="stock" :label="__('Date')" :value="today()->toDateString()" required />
                            </div>
                        </div>
                        <x-form.input name="note" bag="stock" :label="__('Reason')" required :placeholder="__('e.g. Issued to Finance')" />
                        <p class="small text-secondary">{{ __('Stock received from suppliers comes in through purchase orders.') }}</p>
                        <x-submit-button>{{ __('Save') }}</x-submit-button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Stock history') }}</h2>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Date') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Note') }}</th>
                                <th class="text-end pe-3">{{ __('Quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="ps-3 text-nowrap">{{ $movement->date->translatedFormat('j M Y') }}</td>
                                    <td><x-enum-badge :value="$movement->type" /></td>
                                    <td>
                                        @if ($movement->source instanceof \App\Models\PurchaseOrder)
                                            @can('procurement.manage')
                                                <a href="{{ route('admin.purchase_orders.show', $movement->source) }}">{{ $movement->source->number }}</a>
                                            @else
                                                {{ $movement->source->number }}
                                            @endcan
                                        @else
                                            {{ $movement->note }}
                                        @endif
                                        <div class="small text-secondary">{{ $movement->creator?->name }}</div>
                                    </td>
                                    <td @class(['text-end pe-3 text-nowrap fw-medium', 'text-success' => (float) $movement->quantity > 0, 'text-danger' => (float) $movement->quantity < 0])>
                                        {{ (float) $movement->quantity > 0 ? '+' : '' }}{{ (float) $movement->quantity }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-5">{{ __('No stock movements yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($movements->hasPages())
                    <div class="card-body border-top pb-0">
                        {{ $movements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
