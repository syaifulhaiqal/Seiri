<?php

namespace App\Notifications;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;

/**
 * To whoever raised the purchase request: it was approved or rejected.
 */
class PurchaseRequestReviewed extends AppNotification
{
    public function __construct(public PurchaseRequest $purchaseRequest)
    {
        parent::__construct();
    }

    protected function approved(): bool
    {
        return $this->purchaseRequest->status === PurchaseRequestStatus::Approved;
    }

    protected function message(): string
    {
        return match (true) {
            $this->approved() => ':number was approved. You can now raise the purchase order.',
            filled($this->purchaseRequest->review_note) => ':number was rejected: :note',
            default => ':number was rejected.',
        };
    }

    protected function parameters(): array
    {
        return [
            'number' => $this->purchaseRequest->number,
            'note' => (string) $this->purchaseRequest->review_note,
        ];
    }

    protected function url(): string
    {
        return route('admin.purchase_requests.show', $this->purchaseRequest);
    }

    protected function icon(): string
    {
        return $this->approved() ? 'bi-cart-check' : 'bi-cart-x';
    }

    protected function context(): string
    {
        return $this->approved() ? 'success' : 'danger';
    }

    protected function subject(): string
    {
        return $this->approved() ? ':number was approved' : ':number was rejected';
    }

    protected function actionText(): string
    {
        return 'View request';
    }
}
