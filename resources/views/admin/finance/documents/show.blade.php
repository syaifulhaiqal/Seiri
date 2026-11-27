@php
    $route = $type->route();
    $isSales = $type === \App\Enums\InvoiceType::Sales;
    $balanceDue = \App\Support\Money::fromCents($invoice->balanceDueCents());
@endphp

<x-layouts.app :title="$invoice->number">
    <div class="page-header d-print-none">
        <div>
            <a href="{{ route($route . '.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to :type', ['type' => strtolower($type->pluralLabel())]) }}
            </a>
            <h1 class="mt-2">{{ $invoice->number }}</h1>
            <p class="text-secondary mb-0">
                {{ $invoice->contact->name }} · <x-enum-badge :value="$invoice->status" />
                @if ($invoice->isOverdue())
                    <span class="badge rounded-pill badge-soft badge-soft-danger">{{ __('Overdue') }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2" x-data="confirmDelete">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>{{ __('Print') }}
            </button>
            @if ($invoice->isDraft())
                <a href="{{ route($route . '.edit', $invoice) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                </a>
                <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($invoice->number), @js(route($route . '.destroy', $invoice)))">
                    <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                </button>
                <form method="POST" action="{{ route($route . '.issue', $invoice) }}" x-data="formSubmit" x-on:submit="submit()">
                    @csrf
                    <button type="submit" class="btn btn-primary" x-bind:disabled="submitting">
                        <i class="bi bi-send me-1"></i>{{ $isSales ? __('Issue invoice') : __('Approve bill') }}
                    </button>
                </form>
            @endif

            <x-delete-modal :title="__('Delete draft?')" :button="__('Delete draft')">
                {{ __('This permanently deletes') }} <strong x-text="name"></strong>.
            </x-delete-modal>
        </div>
    </div>

    @if ($errors->payment->any())
        <div class="alert alert-danger d-flex gap-2 d-print-none" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ $errors->payment->first() }}</div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4 document-sheet">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                        <div>
                            <div class="fs-4 fw-bold">{{ config('app.name') }}</div>
                            <div class="text-secondary small">{{ $isSales ? __('Tax invoice') : __('Supplier bill') }}</div>
                        </div>
                        <div class="text-md-end">
                            <div class="fs-5 fw-semibold">{{ $invoice->number }}</div>
                            <div class="small text-secondary">{{ __('Date') }}: {{ $invoice->issue_date->translatedFormat('j M Y') }}</div>
                            <div class="small text-secondary">{{ __('Due') }}: {{ $invoice->due_date->translatedFormat('j M Y') }}</div>
                            @if ($invoice->reference)
                                <div class="small text-secondary">{{ __('Ref.') }}: {{ $invoice->reference }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="small text-secondary text-uppercase fw-semibold">{{ $isSales ? __('Bill to') : __('From') }}</div>
                        <div class="fw-medium">{{ $invoice->contact->name }}</div>
                        @if ($invoice->contact->registration_no)
                            <div class="small">{{ $invoice->contact->registration_no }}</div>
                        @endif
                        @if ($invoice->contact->address)
                            <div class="small text-secondary" style="white-space: pre-line">{{ $invoice->contact->address }}</div>
                        @endif
                        @if ($invoice->contact->email)
                            <div class="small text-secondary">{{ $invoice->contact->email }}</div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-0">{{ __('Description') }}</th>
                                    <th class="text-end">{{ __('Qty') }}</th>
                                    <th class="text-end">{{ __('Unit price') }}</th>
                                    <th class="text-end pe-0">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->lines as $line)
                                    <tr>
                                        <td class="ps-0">
                                            {{ $line->description }}
                                            <div class="small text-secondary d-print-none">{{ $line->account->displayName() }}</div>
                                        </td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format((float) $line->quantity, 2), '0'), '.') }}</td>
                                        <td class="text-end text-nowrap">{{ \App\Support\Money::format($line->unit_price, false) }}</td>
                                        <td class="text-end text-nowrap pe-0">{{ \App\Support\Money::format($line->amount, false) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end">{{ __('Subtotal') }}</td>
                                    <td class="text-end text-nowrap pe-0">@money($invoice->subtotal)</td>
                                </tr>
                                @if ($isSales)
                                    <tr>
                                        <td colspan="3" class="text-end">{{ __('Tax (:rate%)', ['rate' => (float) $invoice->tax_rate]) }}</td>
                                        <td class="text-end text-nowrap pe-0">@money($invoice->tax_amount)</td>
                                    </tr>
                                @endif
                                <tr class="fw-semibold">
                                    <td colspan="3" class="text-end">{{ __('Total') }}</td>
                                    <td class="text-end text-nowrap pe-0">@money($invoice->total)</td>
                                </tr>
                                @if ((float) $invoice->amount_paid > 0)
                                    <tr>
                                        <td colspan="3" class="text-end">{{ __('Paid') }}</td>
                                        <td class="text-end text-nowrap pe-0">− @money($invoice->amount_paid)</td>
                                    </tr>
                                    <tr class="fw-semibold">
                                        <td colspan="3" class="text-end">{{ __('Balance due') }}</td>
                                        <td class="text-end text-nowrap pe-0">@money($balanceDue)</td>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>

                    @if ($invoice->notes)
                        <div class="mt-4 small" style="white-space: pre-line">{{ $invoice->notes }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 d-print-none">
            @if ($invoice->isOutstanding())
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ $isSales ? __('Record a payment received') : __('Record a payment made') }}</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route($route . '.pay', $invoice) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                            @csrf
                            <x-form.input name="amount" type="number" step="0.01" min="0.01" bag="payment" :label="__('Amount (:currency)', ['currency' => config('finance.currency')])" :value="$balanceDue" required />
                            <x-form.input name="date" type="date" bag="payment" :label="__('Date')" :value="today()->toDateString()" required />
                            <x-form.select name="account_id" bag="payment" :label="$isSales ? __('Paid into') : __('Paid from')" :options="$bankAccounts" :value="$bankAccounts->keys()->first()" required />
                            <x-form.select name="method" bag="payment" :label="__('Method')" :options="$methods" :value="'bank_transfer'" :placeholder="__('Not recorded')" />
                            <x-form.input name="reference" bag="payment" :label="__('Reference')" :placeholder="__('Bank ref. or cheque no.')" />
                            <x-submit-button>{{ __('Record payment') }}</x-submit-button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header">
                    <h2>{{ __('Payments') }}</h2>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($invoice->payments as $payment)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-medium">@money($payment->amount)</span>
                                <span class="small text-secondary">{{ $payment->date->translatedFormat('j M Y') }}</span>
                            </div>
                            <div class="small text-secondary">
                                {{ $payment->account->name }}{{ $payment->method ? ' · ' . $payment->method->label() : '' }}{{ $payment->reference ? ' · ' . $payment->reference : '' }}
                            </div>
                            @if ($payment->journal_entry_id)
                                <a href="{{ route('admin.journal_entries.show', $payment->journal_entry_id) }}" class="small">{{ __('Journal entry') }}</a>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item small text-secondary">{{ __('No payments yet.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2>{{ __('Ledger') }}</h2>
                </div>
                <div class="card-body small">
                    @if ($invoice->journalEntry)
                        <p class="mb-2">
                            {{ __('Posted as') }} <a href="{{ route('admin.journal_entries.show', $invoice->journalEntry) }}">{{ $invoice->journalEntry->number }}</a>
                            {{ __('on :date', ['date' => $invoice->issued_at?->translatedFormat('j M Y')]) }}.
                        </p>
                    @else
                        <p class="text-secondary mb-2">{{ __('Not posted yet. Drafts do not affect the accounts.') }}</p>
                    @endif

                    @if ($invoice->voided_at)
                        <p class="text-secondary mb-0">{{ __('Voided on :date. The posting was reversed.', ['date' => $invoice->voided_at->translatedFormat('j M Y')]) }}</p>
                    @endif
                </div>
            </div>

            @if (in_array($invoice->status, [\App\Enums\InvoiceStatus::Open], true) && (float) $invoice->amount_paid === 0.0)
                <div class="card border-danger-subtle">
                    <div class="card-header">
                        <h2 class="text-danger">{{ __('Void') }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="small text-secondary">{{ __('Cancels this document and reverses its ledger posting. The number is kept, so the sequence has no gaps.') }}</p>
                        <form method="POST" action="{{ route($route . '.void', $invoice) }}" x-data="formSubmit" x-on:submit="if (! confirm(@js(__('Void :number? This cannot be undone.', ['number' => $invoice->number])))) { $event.preventDefault() } else { submit() }">
                            @csrf
                            <x-form.input name="reason" :label="__('Reason')" :placeholder="__('Optional')" />
                            <button type="submit" class="btn btn-outline-danger" x-bind:disabled="submitting">
                                <i class="bi bi-x-octagon me-1"></i>{{ __('Void :type', ['type' => strtolower($type->label())]) }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
