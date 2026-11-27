@php
    $lines = array_values(old('lines', []));
@endphp

<x-layouts.app :title="__('New journal entry')">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.journal_entries.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to journal') }}
            </a>
            <h1 class="mt-2">{{ __('New journal entry') }}</h1>
            <p class="text-secondary mb-0">{{ __('For adjustments, opening balances and anything without its own page. Debits must equal credits.') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.journal_entries.store') }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
        @csrf

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-4 col-lg-3">
                        <x-form.input name="date" type="date" :label="__('Date')" :value="today()->toDateString()" required />
                    </div>
                    <div class="col-sm-8 col-lg-6">
                        <x-form.input name="description" :label="__('Description')" required />
                    </div>
                    <div class="col-lg-3">
                        <x-form.input name="reference" :label="__('Reference')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4" x-data="journalLines(@js($lines))">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2>{{ __('Lines') }}</h2>
                <button type="button" class="btn btn-sm btn-outline-primary" x-on:click="add()">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Add line') }}
                </button>
            </div>
            @include('partials.line-errors')
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3" style="min-width: 16rem">{{ __('Account') }}</th>
                            <th style="min-width: 12rem">{{ __('Line description') }}</th>
                            <th class="text-end" style="width: 10rem">{{ __('Debit') }}</th>
                            <th class="text-end" style="width: 10rem">{{ __('Credit') }}</th>
                            <th class="pe-3" style="width: 3rem"><span class="visually-hidden">{{ __('Remove') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" x-bind:key="index">
                            <tr>
                                <td class="ps-3">
                                    <select class="form-select" x-model="line.account_id" x-bind:name="`lines[${index}][account_id]`" aria-label="{{ __('Account') }}">
                                        <option value="">{{ __('Choose an account') }}</option>
                                        @foreach ($accounts as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" x-model="line.description" x-bind:name="`lines[${index}][description]`" aria-label="{{ __('Line description') }}">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control text-end" x-model="line.debit" x-bind:name="`lines[${index}][debit]`" x-on:input="if (line.debit) line.credit = ''" aria-label="{{ __('Debit') }}">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control text-end" x-model="line.credit" x-bind:name="`lines[${index}][credit]`" x-on:input="if (line.credit) line.debit = ''" aria-label="{{ __('Credit') }}">
                                </td>
                                <td class="pe-3 text-end">
                                    <button type="button" class="btn btn-sm btn-link text-secondary" x-on:click="remove(index)" x-bind:disabled="lines.length <= 2" title="{{ __('Remove line') }}">
                                        <i class="bi bi-x-lg"></i><span class="visually-hidden">{{ __('Remove line') }}</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td class="ps-3" colspan="2">
                                <span x-show="balanced" class="text-success"><i class="bi bi-check-circle me-1"></i>{{ __('Balanced') }}</span>
                                <span x-show="! balanced" x-cloak class="text-danger">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ __('Out by') }} {{ config('finance.currency') }} <span x-text="format(Math.abs(debit - credit))"></span>
                                </span>
                            </td>
                            <td class="text-end" x-text="format(debit)"></td>
                            <td class="text-end" x-text="format(credit)"></td>
                            <td class="pe-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2">
            <x-submit-button>{{ __('Post entry') }}</x-submit-button>
            <a href="{{ route('admin.journal_entries.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
