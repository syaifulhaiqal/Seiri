@php
    $editing = $order->exists;
    $backUrl = $editing ? route('admin.purchase_orders.show', $order) : route('admin.purchase_orders.index');
@endphp

<x-layouts.app :title="$editing ? __('Edit :number', ['number' => $order->number]) : __('New purchase order')">
    <div class="page-header">
        <div>
            <a href="{{ $backUrl }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ $editing ? __('Back to :number', ['number' => $order->number]) : __('Back to purchase orders') }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit :number', ['number' => $order->number]) : __('New purchase order') }}</h1>
            <p class="text-secondary mb-0">
                @if ($fromRequest)
                    {{ __('From :number: :purpose', ['number' => $fromRequest->number, 'purpose' => $fromRequest->purpose]) }}
                @else
                    {{ __('Saved as a draft. Mark it as sent once it goes to the supplier.') }}
                @endif
            </p>
        </div>
    </div>

    @if ($suppliers->isEmpty())
        <div class="alert alert-warning d-flex gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                {{ __('Add a supplier first.') }}
                @can('invoices.manage')
                    <a href="{{ route('admin.contacts.create', ['type' => 'supplier']) }}">{{ __('Add contact') }}</a>
                @endcan
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.purchase_orders.update', $order) : route('admin.purchase_orders.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
        @csrf
        @if ($editing)
            @method('PUT')
        @elseif ($fromRequest)
            <input type="hidden" name="purchase_request_id" value="{{ $fromRequest->id }}">
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <x-form.select name="contact_id" :label="__('Supplier')" :options="$suppliers" :value="$order->contact_id" :placeholder="__('Choose…')" required />
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <x-form.input name="order_date" type="date" :label="__('Order date')" :value="$order->order_date?->toDateString()" required />
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <x-form.input name="expected_date" type="date" :label="__('Expected delivery')" :value="$order->expected_date?->toDateString()" />
                    </div>
                </div>
                @error('purchase_request_id')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @include('partials.item-lines', ['lines' => $lines, 'products' => $products, 'priceKey' => 'cost'])

        <div class="card mb-4">
            <div class="card-body">
                <x-form.textarea name="notes" :label="__('Notes to the supplier')" :value="$order->notes" rows="2" :help="__('e.g. delivery address, contact person, payment terms.')" />
            </div>
        </div>

        <div class="d-flex gap-2">
            <x-submit-button>{{ $editing ? __('Save draft') : __('Save as draft') }}</x-submit-button>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
