<?php

namespace App\Notifications;

use App\Models\PayrollRun;
use App\Support\Money;

/**
 * To admins who approve payroll: a month has been prepared.
 */
class PayrollAwaitingApproval extends AppNotification
{
    public function __construct(public PayrollRun $run)
    {
        parent::__construct();
    }

    protected function message(): string
    {
        return 'Payroll for :month is ready for approval: :count payslips, :amount net pay.';
    }

    protected function parameters(): array
    {
        return [
            'month' => $this->run->periodLabel(),
            'count' => $this->run->employee_count,
            'amount' => Money::format($this->run->total_net),
        ];
    }

    protected function url(): string
    {
        return route('admin.payroll.show', $this->run);
    }

    protected function icon(): string
    {
        return 'bi-wallet2';
    }

    protected function context(): string
    {
        return 'warning';
    }

    protected function subject(): string
    {
        return 'Payroll for :month needs approval';
    }

    protected function actionText(): string
    {
        return 'Review payroll';
    }
}
