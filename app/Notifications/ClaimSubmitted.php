<?php

namespace App\Notifications;

use App\Models\ExpenseClaim;
use App\Support\Money;

/**
 * To admins who review claims: a new expense claim is waiting.
 */
class ClaimSubmitted extends AppNotification
{
    public function __construct(public ExpenseClaim $claim)
    {
        parent::__construct();
    }

    protected function message(): string
    {
        return ':name claimed :amount for :description.';
    }

    protected function parameters(): array
    {
        return [
            'name' => $this->claim->employee->name,
            'amount' => Money::format($this->claim->amount),
            'description' => $this->claim->description,
            'number' => $this->claim->number,
        ];
    }

    protected function url(): string
    {
        return route('admin.claims.show', $this->claim);
    }

    protected function icon(): string
    {
        return 'bi-receipt';
    }

    protected function context(): string
    {
        return 'warning';
    }

    protected function subject(): string
    {
        return 'Expense claim :number from :name';
    }

    protected function actionText(): string
    {
        return 'Review claim';
    }
}
