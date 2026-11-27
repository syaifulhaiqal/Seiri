@php
    $status = $quotation->status;
    $statuses = \App\Enums\QuotationStatus::class;
@endphp

<x-layouts.app :title="$quotation->number">
    <div class="page-header d-print-none">
        <div>
            <a href="{{ route('admin.quotations.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to quotations') }}
            </a>
            <h1 class="mt-2">{{ $quotation->number }}</h1>
            <p class="text-secondary mb-0">
                {{ $quotation->contact->name }} · <x-enum-badge :value="$status" />
                @if ($quotation->isExpired())
                    <span class="badge rounded-pill badge-soft badge-soft-danger">{{ __('Expired') }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2" x-data="confirmDelete">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>{{ __('Print') }}
            </button>
            @if ($quotation->isDraft())
                <a href="{{ route('admin.quotations.edit', $quotation) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>
                <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($quotation->number), @js(route('admin.quotations.destroy', $quotation)))">
                    <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                </button>
            @endif
            @foreach ([
                [$statuses::Draft, 'sent', __('Mark as sent'), 'btn-primary', 'bi-send'],
                [$statuses::Sent, 'declined', __('Declined'), 'btn-outline-danger', 'bi-x-lg'],
                [$statuses::Sent, 'accepted', __('Accepted'), 'btn-outline-success', 'bi-check-lg'],
            ] as [$from, $to, $label, $class, $icon])
                @if ($status === $from)
                    <form method="POST" action="{{ route('admin.quotations.mark', $quotation) }}" x-data="formSubmit" x-on:submit="submit()">
                        @csrf
                        <input type="hidden" name="status" value="{{ $to }}">
                        <button type="submit" class="btn {{ $class }}" x-bind:disabled="submitting"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</button>
                    </form>
                @endif
            @endforeach
            @if ($quotation->canBeInvoiced())
                @can('invoices.manage')
                    <form method="POST" action="{{ route('admin.quotations.invoice', $quotation) }}" x-data="formSubmit" x-on:submit="submit()">
                        @csrf
                        <button type="submit" class="btn btn-primary" x-bind:disabled="submitting"><i class="bi bi-file-earmark-text me-1"></i>{{ __('Create invoice') }}</button>
                    </form>
                @endcan
            @endif

            <x-delete-modal :title="__('Delete quotation?')" :button="__('Delete quotation')">
                {{ __('This permanently deletes the draft') }} <strong x-text="name"></strong>.
            </x-delete-modal>
        </div>
    </div>

    @error('delete')
        <div class="alert alert-danger d-flex gap-2 d-print-none" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card document-sheet">
                <div class="card-body p-4 p-md-5 pb-0 pb-md-0">
                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                        <div>
                            <div class="fs-4 fw-bold">{{ config('payroll.employer.name') }}</div>
                            <div class="text-secondary small">{{ __('Quotation') }}</div>
                        </div>
                        <div class="text-md-end small">
                            <div class="fs-5 fw-semibold">{{ $quotation->number }}</div>
                            <div>{{ __('Date: :date', ['date' => $quotation->issue_date->translatedFormat('j M Y')]) }}</div>
                            <div>{{ __('Valid until: :date', ['date' => $quotation->valid_until->translatedFormat('j M Y')]) }}</div>
                        </div>
                    </div>
                    <div class="small mb-4">
                        <div class="text-secondary">{{ __('Prepared for') }}</div>
                        <div class="fw-semibold">{{ $quotation->contact->name }}</div>
                        @if ($quotation->contact->address)
                            <div class="text-secondary" style="white-space: pre-line">{{ $quotation->contact->address }}</div>
                        @endif
                    </div>
                </div>
                @include('partials.item-lines-table', ['document' => $quotation, 'totals' => [
                    __('Subtotal') => $quotation->subtotal,
                    __('Tax (:rate%)', ['rate' => (float) $quotation->tax_rate]) => $quotation->tax_amount,
                    __('Total') => $quotation->total,
                ]])
                @if ($quotation->notes)
                    <div class="card-body px-4 px-md-5 small" style="white-space: pre-line">{{ $quotation->notes }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-4 d-print-none">
            <div class="card">
                <div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-secondary fw-normal">{{ __('Prepared by') }}</dt>
                        <dd class="col-7">{{ $quotation->creator?->name ?? '—' }}</dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Lead') }}</dt>
                        <dd class="col-7">
                            @if ($quotation->lead)
                                <a href="{{ route('admin.leads.show', $quotation->lead) }}">{{ $quotation->lead->title }}</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Invoice') }}</dt>
                        <dd class="col-7 mb-0">
                            @if ($quotation->invoice)
                                @can('invoices.manage')
                                    <a href="{{ route('admin.invoices.show', $quotation->invoice) }}">{{ $quotation->invoice->number }}</a>
                                @else
                                    {{ $quotation->invoice->number }}
                                @endcan
                                <x-enum-badge :value="$quotation->invoice->status" class="ms-1" />
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
