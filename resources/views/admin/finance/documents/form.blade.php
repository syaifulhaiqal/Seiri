@php
    $editing = $invoice->exists;
    $route = $type->route();
    $isSales = $type === \App\Enums\InvoiceType::Sales;
    $backUrl = $editing ? route($route . '.show', $invoice) : route($route . '.index');
    $lines = array_values(old('lines', $invoice->lines->map(fn ($line) => [
        'account_id' => $line->account_id,
        'description' => $line->description,
        'quantity' => (float) $line->quantity,
        'unit_price' => $line->unit_price,
    ])->all()));
@endphp

<x-layouts.app :title="$editing ? __('Edit :number', ['number' => $invoice->number]) : __('New :type', ['type' => strtolower($type->label())])">
    <div class="page-header">
        <div>
            <a href="{{ $backUrl }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ $editing ? __('Back to :number', ['number' => $invoice->number]) : __('Back to :type', ['type' => strtolower($type->pluralLabel())]) }}
            </a>
            <h1 class="mt-2">{{ $editing ? __('Edit :number', ['number' => $invoice->number]) : __('New :type', ['type' => strtolower($type->label())]) }}</h1>
            <p class="text-secondary mb-0">{{ __('Saved as a draft. Nothing is posted to the ledger until you issue it.') }}</p>
        </div>
    </div>

    @if ($contacts->isEmpty())
        <div class="alert alert-warning d-flex gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                {{ __('Add a :contact first.', ['contact' => strtolower($type->contactLabel())]) }}
                <a href="{{ route('admin.contacts.create', ['type' => $isSales ? 'customer' : 'supplier']) }}">{{ __('Add contact') }}</a>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route($route . '.update', $invoice) : route($route . '.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div x-data="invoiceLines({ lines: @js($lines), taxRate: @js((float) old('tax_rate', $invoice->tax_rate)), defaultAccount: @js($defaultAccountId) })">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.select name="contact_id" :label="$type->contactLabel()" :options="$contacts" :value="$invoice->contact_id" :placeholder="__('Choose…')" required />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="reference" :label="$isSales ? __('Reference / PO number') : __('Supplier invoice number')" :value="$invoice->reference" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <x-form.input name="issue_date" type="date" :label="$isSales ? __('Invoice date') : __('Bill date')" :value="$invoice->issue_date?->toDateString()" required />
                        </div>
                        <div class="col-sm-4">
                            <x-form.input name="due_date" type="date" :label="__('Due date')" :value="$invoice->due_date?->toDateString()" required />
                        </div>
                        @if ($isSales)
                            <div class="col-sm-4">
                                <div class="mb-3">
                                    <label for="tax_rate" class="form-label fw-medium">{{ __('Tax rate (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100" id="tax_rate" name="tax_rate" x-model="taxRate" @class(['form-control', 'is-invalid' => $errors->has('tax_rate')])>
                                    @error('tax_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">{{ __('Sales and service tax, posted to Sales tax payable.') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2>{{ __('Items') }}</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" x-on:click="add()">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Add item') }}
                    </button>
                </div>
                @include('partials.line-errors')
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3" style="min-width: 16rem">{{ __('Description') }}</th>
                                <th style="min-width: 13rem">{{ $isSales ? __('Income account') : __('Expense account') }}</th>
                                <th class="text-end" style="width: 7rem">{{ __('Qty') }}</th>
                                <th class="text-end" style="width: 9rem">{{ __('Unit price') }}</th>
                                <th class="text-end" style="width: 9rem">{{ __('Amount') }}</th>
                                <th class="pe-3" style="width: 3rem"><span class="visually-hidden">{{ __('Remove') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(line, index) in lines" x-bind:key="index">
                                <tr>
                                    <td class="ps-3">
                                        <input type="text" class="form-control" x-model="line.description" x-bind:name="`lines[${index}][description]`" aria-label="{{ __('Description') }}">
                                    </td>
                                    <td>
                                        <select class="form-select" x-model="line.account_id" x-bind:name="`lines[${index}][account_id]`" aria-label="{{ __('Account') }}">
                                            @foreach ($lineAccounts as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control text-end" x-model="line.quantity" x-bind:name="`lines[${index}][quantity]`" aria-label="{{ __('Quantity') }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control text-end" x-model="line.unit_price" x-bind:name="`lines[${index}][unit_price]`" aria-label="{{ __('Unit price') }}">
                                    </td>
                                    <td class="text-end text-nowrap" x-text="format(amount(line))"></td>
                                    <td class="pe-3 text-end">
                                        <button type="button" class="btn btn-sm btn-link text-secondary" x-on:click="remove(index)" x-bind:disabled="lines.length <= 1" title="{{ __('Remove item') }}">
                                            <i class="bi bi-x-lg"></i><span class="visually-hidden">{{ __('Remove item') }}</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end ps-3">{{ __('Subtotal') }}</td>
                                <td class="text-end text-nowrap" x-text="format(subtotal)"></td>
                                <td class="pe-3"></td>
                            </tr>
                            @if ($isSales)
                                <tr>
                                    <td colspan="4" class="text-end ps-3">{{ __('Tax') }} (<span x-text="taxRate || 0"></span>%)</td>
                                    <td class="text-end text-nowrap" x-text="format(tax)"></td>
                                    <td class="pe-3"></td>
                                </tr>
                            @endif
                            <tr class="fw-semibold">
                                <td colspan="4" class="text-end ps-3">{{ __('Total') }} ({{ config('finance.currency') }})</td>
                                <td class="text-end text-nowrap" x-text="format(total)"></td>
                                <td class="pe-3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <x-form.textarea name="notes" :label="$isSales ? __('Notes to the customer') : __('Notes')" :value="$invoice->notes" rows="2" :help="$isSales ? __('Shown on the invoice, e.g. bank details or payment terms.') : null" />
            </div>
        </div>

        <div class="d-flex gap-2">
            <x-submit-button>{{ $editing ? __('Save draft') : __('Save as draft') }}</x-submit-button>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
