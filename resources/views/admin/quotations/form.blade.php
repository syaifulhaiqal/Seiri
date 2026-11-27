@php
    $editing = $quotation->exists;
    $backUrl = $editing ? route('admin.quotations.show', $quotation) : ($lead ? route('admin.leads.show', $lead) : route('admin.quotations.index'));
@endphp

<x-layouts.app :title="$editing ? __('Edit :number', ['number' => $quotation->number]) : __('New quotation')">
    <div class="page-header">
        <div>
            <a href="{{ $backUrl }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ $editing ? __('Back to :number', ['number' => $quotation->number]) : ($lead ? __('Back to :name', ['name' => $lead->title]) : __('Back to quotations')) }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit :number', ['number' => $quotation->number]) : __('New quotation') }}</h1>
            <p class="text-secondary mb-0">{{ $lead ? __('For lead: :name', ['name' => $lead->title]) : __('Saved as a draft. Nothing is posted to the ledger.') }}</p>
        </div>
    </div>

    @if ($customers->isEmpty())
        <div class="alert alert-warning d-flex gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <div>{{ __('Add a customer first, or make a lead a customer.') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.quotations.update', $quotation) : route('admin.quotations.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
        @csrf
        @if ($editing)
            @method('PUT')
        @elseif ($lead)
            <input type="hidden" name="lead_id" value="{{ $lead->id }}">
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <x-form.select name="contact_id" :label="__('Customer')" :options="$customers" :value="$quotation->contact_id" :placeholder="__('Choose…')" required />
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <x-form.input name="issue_date" type="date" :label="__('Date')" :value="$quotation->issue_date?->toDateString()" required />
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <x-form.input name="valid_until" type="date" :label="__('Valid until')" :value="$quotation->valid_until?->toDateString()" required />
                    </div>
                </div>
            </div>
        </div>

        @include('partials.item-lines', ['lines' => $lines, 'products' => $products, 'priceKey' => 'price', 'withTax' => true, 'taxRate' => $quotation->tax_rate])

        <div class="card mb-4">
            <div class="card-body">
                <x-form.textarea name="notes" :label="__('Notes to the customer')" :value="$quotation->notes" rows="2" :help="__('e.g. delivery time, payment terms, what is not included.')" />
            </div>
        </div>

        <div class="d-flex gap-2">
            <x-submit-button>{{ $editing ? __('Save draft') : __('Save as draft') }}</x-submit-button>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
