<?php

namespace App\Notifications;

use App\Models\Approval;

/**
 * To the approver whose turn it is in an employee's approval chain.
 */
class ApprovalRequested extends AppNotification
{
    public function __construct(public Approval $approval)
    {
        parent::__construct();
    }

    protected function message(): string
    {
        return ':name is waiting for your approval: :summary';
    }

    protected function parameters(): array
    {
        $request = $this->approval->approvable;

        return [
            'name' => $request->employee->name,
            'summary' => $request->approvalSummary(),
        ];
    }

    protected function url(): string
    {
        return route('approvals.index');
    }

    protected function icon(): string
    {
        return 'bi-check2-square';
    }

    protected function context(): string
    {
        return 'warning';
    }

    protected function subject(): string
    {
        return 'Approval needed for :name';
    }

    protected function actionText(): string
    {
        return 'Review and decide';
    }
}
