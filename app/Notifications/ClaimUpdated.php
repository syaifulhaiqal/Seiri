<?php

namespace App\Notifications;

use App\Enums\ClaimStatus;
use App\Models\ExpenseClaim;
use App\Support\Money;

/**
 * To the employee: their claim was approved, rejected or paid back.
 */
class ClaimUpdated extends AppNotification
{
    public function __construct(public ExpenseClaim $claim)
    {
        parent::__construct();
    }

    protected function message(): string
    {
        return match (true) {
            $this->claim->status === ClaimStatus::Reimbursed => 'Your claim :number for :amount has been paid back.',
            $this->claim->status === ClaimStatus::Approved => 'Your claim :number for :amount was approved. It will be paid back soon.',
            filled($this->claim->review_note) => 'Your claim :number for :amount was rejected: :note',
            default => 'Your claim :number for :amount was rejected.',
        };
    }

    protected function parameters(): array
    {
        return [
            'number' => $this->claim->number,
            'amount' => Money::format($this->claim->amount),
            'note' => (string) $this->claim->review_note,
        ];
    }

    protected function url(): string
    {
        return route('claims.index');
    }

    protected function icon(): string
    {
        return match ($this->claim->status) {
            ClaimStatus::Reimbursed => 'bi-cash-coin',
            ClaimStatus::Rejected => 'bi-x-circle',
            default => 'bi-check-circle',
        };
    }

    protected function context(): string
    {
        return $this->claim->status === ClaimStatus::Rejected ? 'danger' : 'success';
    }

    protected function subject(): string
    {
        return match ($this->claim->status) {
            ClaimStatus::Reimbursed => 'Claim :number paid back',
            ClaimStatus::Rejected => 'Claim :number rejected',
            default => 'Claim :number approved',
        };
    }

    protected function actionText(): string
    {
        return 'View my claims';
    }
}
