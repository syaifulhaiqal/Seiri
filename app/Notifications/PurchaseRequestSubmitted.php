<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use App\Support\Money;

/**
 * To admins who approve purchasing: a new purchase request is waiting.
 */
class PurchaseRequestSubmitted extends AppNotification
{
    public function __construct(public PurchaseRequest $purchaseRequest)
    {
        parent::__construct();
    }

    protected function message(): string
    {
        return ':name raised :number for :total: :purpose';
    }

    protected function parameters(): array
    {
        return [
            'name' => $this->purchaseRequest->requester?->name ?? '—',
            'number' => $this->purchaseRequest->number,
            'total' => Money::format($this->purchaseRequest->total),
            'purpose' => $this->purchaseRequest->purpose,
        ];
    }

    protected function url(): string
    {
        return route('admin.purchase_requests.show', $this->purchaseRequest);
    }

    protected function icon(): string
    {
        return 'bi-cart-plus';
    }

    protected function context(): string
    {
        return 'warning';
    }

    protected function subject(): string
    {
        return 'Purchase request :number needs approval';
    }

    protected function actionText(): string
    {
        return 'Review request';
    }
}
