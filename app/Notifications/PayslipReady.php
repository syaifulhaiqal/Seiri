<?php

namespace App\Notifications;

use App\Models\Payslip;
use App\Support\Money;

/**
 * To the employee: their payslip is approved and can be viewed.
 */
class PayslipReady extends AppNotification
{
    public function __construct(public Payslip $payslip)
    {
        parent::__construct();
    }

    protected function message(): string
    {
        return 'Your payslip for :month is ready. Net pay: :amount.';
    }

    protected function parameters(): array
    {
        return [
            'month' => $this->payslip->payrollRun->periodLabel(),
            'amount' => Money::format($this->payslip->net_pay),
        ];
    }

    protected function url(): string
    {
        return route('payslips.show', $this->payslip);
    }

    protected function icon(): string
    {
        return 'bi-wallet2';
    }

    protected function context(): string
    {
        return 'success';
    }

    protected function subject(): string
    {
        return 'Your payslip for :month';
    }

    protected function actionText(): string
    {
        return 'View payslip';
    }
}
