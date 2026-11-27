<x-layouts.app :title="$order->number">
    <div class="page-header d-print-none">
        <div>
            <a href="{{ route('admin.purchase_orders.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to purchase orders') }}
            </a>
            <h1 class="mt-2">{{ $order->number }}</h1>
            <p class="text-secondary mb-0">{{ $order->contact->name }} · <x-enum-badge :value="$order->status" /></p>
        </div>
        <div class="d-flex flex-wrap gap-2" x-data="confirmDelete">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>{{ __('Print') }}
            </button>
            @if ($order->isDraft())
                <a href="{{ route('admin.purchase_orders.edit', $order) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>
                <button type="button" class="btn btn-outline-danger" x-on:click="open(@js($order->number), @js(route('admin.purchase_orders.destroy', $order)))">
                    <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                </button>
                <form method="POST" action="{{ route('admin.purchase_orders.place', $order) }}" x-data="formSubmit" x-on:submit="submit()">
                    @csrf
                    <button type="submit" class="btn btn-primary" x-bind:disabled="submitting"><i class="bi bi-send me-1"></i>{{ __('Mark as sent') }}</button>
                </form>
            @endif
            @if ($order->status === \App\Enums\PurchaseOrderStatus::Ordered)
                <form method="POST" action="{{ route('admin.purchase_orders.cancel', $order) }}" x-data="formSubmit" x-on:submit="submit()">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" x-bind:disabled="submitting">{{ __('Cancel order') }}</button>
                </form>
            @endif
            @if ($order->canBeBilled())
                @can('invoices.manage')
                    <form method="POST" action="{{ route('admin.purchase_orders.bill', $order) }}" x-data="formSubmit" x-on:submit="submit()">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary" x-bind:disabled="submitting"><i class="bi bi-file-earmark-minus me-1"></i>{{ __('Create bill') }}</button>
                    </form>
                @endcan
            @endif

            <x-delete-modal :title="__('Delete purchase order?')" :button="__('Delete order')">
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
                            <div class="text-secondary small">{{ __('Purchase order') }}</div>
                        </div>
                        <div class="text-md-end small">
                            <div class="fs-5 fw-semibold">{{ $order->number }}</div>
                            <div>{{ __('Date: :date', ['date' => $order->order_date->translatedFormat('j M Y')]) }}</div>
                            @if ($order->expected_date)
                                <div>{{ __('Deliver by: :date', ['date' => $order->expected_date->translatedFormat('j M Y')]) }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="small mb-4">
                        <div class="text-secondary">{{ __('Supplier') }}</div>
                        <div class="fw-semibold">{{ $order->contact->name }}</div>
                        @if ($order->contact->address)
                            <div class="text-secondary" style="white-space: pre-line">{{ $order->contact->address }}</div>
                        @endif
                    </div>
                </div>
                @include('partials.item-lines-table', ['document' => $order, 'totals' => [__('Total') => $order->total]])
                @if ($order->notes)
                    <div class="card-body px-4 px-md-5 small" style="white-space: pre-line">{{ $order->notes }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-4 d-print-none">
            @if ($order->canBeReceived())
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>{{ __('Receive goods') }}</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.purchase_orders.receive', $order) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                            @csrf
                            <x-form.input name="date" type="date" :label="__('Date received')" :value="today()->toDateString()" required />
                            <p class="small text-secondary">{{ __('Lines linked to a product are added to stock at the order price.') }}</p>
                            <x-submit-button>{{ __('Mark as received') }}</x-submit-button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-secondary fw-normal">{{ __('Raised by') }}</dt>
                        <dd class="col-7">{{ $order->creator?->name ?? '—' }}</dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Request') }}</dt>
                        <dd class="col-7">
                            @if ($order->purchaseRequest)
                                <a href="{{ route('admin.purchase_requests.show', $order->purchaseRequest) }}">{{ $order->purchaseRequest->number }}</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Received') }}</dt>
                        <dd class="col-7">{{ $order->received_at?->translatedFormat('j M Y') ?? '—' }}</dd>
                        <dt class="col-5 text-secondary fw-normal">{{ __('Bill') }}</dt>
                        <dd class="col-7 mb-0">
                            @if ($order->bill)
                                @can('invoices.manage')
                                    <a href="{{ route('admin.bills.show', $order->bill) }}">{{ $order->bill->number }}</a>
                                @else
                                    {{ $order->bill->number }}
                                @endcan
                                <x-enum-badge :value="$order->bill->status" class="ms-1" />
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
