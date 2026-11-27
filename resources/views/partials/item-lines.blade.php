{{--
    Editable item lines with an optional product per line. Expects:
    $lines (array of rows), $products (Product::pickerOptions()), $priceKey ('cost' or 'price'),
    $withTax (bool: show a tax rate field bound to the form's tax_rate input), $taxRate.
--}}
@php
    $withTax ??= false;
    $rows = array_values(old('lines', $lines));
@endphp

<div class="card mb-4" x-data="itemLines({ lines: @js($rows), products: @js((object) $products), priceKey: @js($priceKey), taxRate: @js((float) old('tax_rate', $taxRate ?? 0)) })">
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
                    <th class="ps-3" style="min-width: 12rem">{{ __('Product') }}</th>
                    <th style="min-width: 16rem">{{ __('Description') }}</th>
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
                            <select class="form-select" x-model="line.product_id" x-on:change="pick(line)" x-bind:name="`lines[${index}][product_id]`" aria-label="{{ __('Product') }}">
                                <option value="">{{ __('No product (service or one-off)') }}</option>
                                @foreach ($products as $id => $product)
                                    <option value="{{ $id }}">{{ $product['label'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" x-model="line.description" x-bind:name="`lines[${index}][description]`" aria-label="{{ __('Description') }}">
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
                @if ($withTax)
                    <tr>
                        <td colspan="4" class="text-end ps-3">{{ __('Subtotal') }}</td>
                        <td class="text-end text-nowrap" x-text="format(subtotal)"></td>
                        <td class="pe-3"></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end ps-3 align-middle">{{ __('Tax rate (%)') }}</td>
                        <td>
                            <input type="number" step="0.01" min="0" max="100" name="tax_rate" x-model="taxRate" @class(['form-control text-end', 'is-invalid' => $errors->has('tax_rate')]) aria-label="{{ __('Tax rate (%)') }}">
                        </td>
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
