<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Support\DateRange;

/**
 * To admins who review leave: a new request is waiting.
 */
class LeaveRequested extends AppNotification
{
    public function __construct(public LeaveRequest $leaveRequest)
    {
        parent::__construct();
    }

    protected function message(): string
    {
        return ':name applied for :type on :dates (:days working days).';
    }

    protected function parameters(): array
    {
        return [
            'name' => $this->leaveRequest->employee->name,
            'type' => $this->leaveRequest->leaveType->name,
            'dates' => DateRange::format($this->leaveRequest->start_date, $this->leaveRequest->end_date),
            'days' => $this->leaveRequest->days,
        ];
    }

    protected function url(): string
    {
        return route('admin.leave_requests.show', $this->leaveRequest);
    }

    protected function icon(): string
    {
        return 'bi-calendar2-plus';
    }

    protected function context(): string
    {
        return 'warning';
    }

    protected function subject(): string
    {
        return 'Leave request from :name';
    }

    protected function actionText(): string
    {
        return 'Review request';
    }
}
