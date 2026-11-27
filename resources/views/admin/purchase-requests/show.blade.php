<x-layouts.app :title="$purchaseRequest->number">
    <div class="page-header">
        <div>
            <a href="{{ route('admin.purchase_requests.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to purchase requests') }}
            </a>
            <h1 class="mt-2">{{ $purchaseRequest->number }}</h1>
            <p class="text-secondary mb-0">
                {{ __('Raised by :name on :date', ['name' => $purchaseRequest->requester?->name ?? '—', 'date' => $purchaseRequest->created_at->translatedFormat('j M Y')]) }}
                · <x-enum-badge :value="$purchaseRequest->status" />
            </p>
        </div>
        @can('procurement.manage')
            <div class="d-flex gap-2">
                @if ($purchaseRequest->canBeOrdered())
                    <a href="{{ route('admin.purchase_orders.create', ['request' => $purchaseRequest->id]) }}" class="btn btn-primary"><i class="bi bi-truck me-1"></i>{{ __('Raise purchase order') }}</a>
                @endif
                @if ($purchaseRequest->isPending())
                    <a href="{{ route('admin.purchase_requests.edit', $purchaseRequest) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>
                @endif
                @if (in_array($purchaseRequest->status, [\App\Enums\PurchaseRequestStatus::Pending, \App\Enums\PurchaseRequestStatus::Approved], true))
                    <form method="POST" action="{{ route('admin.purchase_requests.cancel', $purchaseRequest) }}" x-data="formSubmit" x-on:submit="submit()">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-outline-danger" x-bind:disabled="submitting">{{ __('Cancel request') }}</button>
                    </form>
                @endif
            </div>
        @endcan
    </div>

    @error('review')
        <div class="alert alert-danger d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-secondary fw-normal">{{ __('Purpose') }}</dt>
                        <dd class="col-sm-9">{{ $purchaseRequest->purpose }}</dd>
                        <dt class="col-sm-3 text-secondary fw-normal">{{ __('Needed by') }}</dt>
                        <dd class="col-sm-9">{{ $purchaseRequest->needed_by?->translatedFormat('l, j M Y') ?? '—' }}</dd>
                        @if ($purchaseRequest->reviewed_at)
                            <dt class="col-sm-3 text-secondary fw-normal">{{ __('Reviewed') }}</dt>
                            <dd class="col-sm-9">{{ __(':name on :date', ['name' => $purchaseRequest->reviewer?->name ?? __('Unknown'), 'date' => $purchaseRequest->reviewed_at->translatedFormat('j M Y')]) }}</dd>
                            <dt class="col-sm-3 text-secondary fw-normal">{{ __('Note') }}</dt>
                            <dd class="col-sm-9 mb-0">{{ $purchaseRequest->review_note ?: '—' }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>{{ __('Items') }}</h2>
                </div>
                @include('partials.item-lines-table', ['document' => $purchaseRequest, 'totals' => [__('Estimated total') => $purchaseRequest->total]])
            </div>
        </div>

        <div class="col-lg-4">
            @if ($purchaseRequest->isPending())
                @can('procurement.approve')
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2>{{ __('Decision') }}</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.purchase_requests.review', $purchaseRequest) }}" x-data="formSubmit" x-on:submit="submit()" novalidate>
                                @csrf
                                @method('PUT')
                                <x-form.textarea name="review_note" :label="__('Note')" rows="2" :help="__('Optional when approving. Required when rejecting.')" />
                                <div class="d-flex gap-2">
                                    <button type="submit" name="decision" value="approve" class="btn btn-success" x-bind:disabled="submitting"><i class="bi bi-check-lg me-1"></i>{{ __('Approve') }}</button>
                                    <button type="submit" name="decision" value="reject" class="btn btn-outline-danger" x-bind:disabled="submitting"><i class="bi bi-x-lg me-1"></i>{{ __('Reject') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">{{ __('Waiting for someone who can approve purchase requests.') }}</div>
                @endcan
            @endif

            @if ($purchaseRequest->purchaseOrders->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h2>{{ __('Purchase orders') }}</h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($purchaseRequest->purchaseOrders as $order)
                            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <span>
                                    @can('procurement.manage')
                                        <a href="{{ route('admin.purchase_orders.show', $order) }}">{{ $order->number }}</a>
                                    @else
                                        {{ $order->number }}
                                    @endcan
                                    <span class="small text-secondary d-block">{{ $order->contact->name }}</span>
                                </span>
                                <x-enum-badge :value="$order->status" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
