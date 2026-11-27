@php
    $editing = $product->exists;
    $backUrl = $editing ? route('admin.products.show', $product) : route('admin.products.index');
@endphp

<x-layouts.app :title="$editing ? __('Edit :name', ['name' => $product->name]) : __('Add product')">
    <div class="page-header">
        <div>
            <a href="{{ $backUrl }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ $editing ? __('Back to :name', ['name' => $product->name]) : __('Back to products') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit :name', ['name' => $product->name]) : __('Add product') }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-4">
                                <x-form.input name="sku" :label="__('SKU')" :value="$product->sku" required placeholder="PPR-A4" />
                            </div>
                            <div class="col-sm-8">
                                <x-form.input name="name" :label="__('Name')" :value="$product->name" required />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3">
                                <x-form.input name="unit" :label="__('Unit')" :value="$product->unit" required placeholder="pcs" />
                            </div>
                            <div class="col-sm-3">
                                <x-form.input name="cost_price" type="number" step="0.01" min="0" :label="__('Cost price')" :value="$product->cost_price" />
                            </div>
                            <div class="col-sm-3">
                                <x-form.input name="sale_price" type="number" step="0.01" min="0" :label="__('Sale price')" :value="$product->sale_price" />
                            </div>
                            <div class="col-sm-3">
                                <x-form.input name="reorder_level" type="number" step="0.01" min="0" :label="__('Reorder at')" :value="$product->reorder_level" :help="__('0 for no alert.')" />
                            </div>
                        </div>
                        <x-form.textarea name="description" :label="__('Description')" :value="$product->description" rows="2" />

                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch">
                            <input type="checkbox" role="switch" id="is_active" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $product->is_active))>
                            <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                        </div>
                        <div class="form-text">{{ __('Inactive products are hidden from purchase orders and quotations.') }}</div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <x-submit-button>{{ $editing ? __('Save changes') : __('Add product') }}</x-submit-button>
                    <a href="{{ $backUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
