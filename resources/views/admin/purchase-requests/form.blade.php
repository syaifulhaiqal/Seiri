@php
    $editing = $purchaseRequest->exists;
    $backUrl = $editing ? route('admin.purchase_requests.show', $purchaseRequest) : route('admin.purchase_requests.index');
@endphp

<x-layouts.app :title="$editing ? __('Edit :number', ['number' => $purchaseRequest->number]) : __('New purchase request')">
    <div class="page-header">
        <div>
            <a href="{{ $backUrl }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ $editing ? __('Back to :number', ['number' => $purchaseRequest->number]) : __('Back to purchase requests') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit :number', ['number' => $purchaseRequest->number]) : __('New purchase request') }}</h1>
            <p class="text-secondary mb-0">{{ __('Prices are estimates. Once approved, raise a purchase order to a supplier.') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ $editing ? route('admin.purchase_requests.update', $purchaseRequest) : route('admin.purchase_requests.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <x-form.input name="purpose" :label="__('What is it for?')" :value="$purchaseRequest->purpose" required :placeholder="__('e.g. Laptops for two new hires in Finance')" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="needed_by" type="date" :label="__('Needed by')" :value="$purchaseRequest->needed_by?->toDateString()" />
                    </div>
                </div>
            </div>
        </div>

        @include('partials.item-lines', ['lines' => $purchaseRequest->exists ? $purchaseRequest->lineRows() : [], 'products' => $products, 'priceKey' => 'cost'])

        <div class="d-flex gap-2">
            <x-submit-button>{{ $editing ? __('Save changes') : __('Send for approval') }}</x-submit-button>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
