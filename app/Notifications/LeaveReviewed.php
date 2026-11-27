<?php

namespace App\Notifications;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Support\DateRange;

/**
 * To the employee: their leave was approved or rejected.
 */
class LeaveReviewed extends AppNotification
{
    public function __construct(public LeaveRequest $leaveRequest)
    {
        parent::__construct();
    }

    protected function approved(): bool
    {
        return $this->leaveRequest->status === LeaveStatus::Approved;
    }

    protected function message(): string
    {
        return match (true) {
            $this->approved() => 'Your :type for :dates was approved.',
            filled($this->leaveRequest->review_note) => 'Your :type for :dates was rejected: :note',
            default => 'Your :type for :dates was rejected.',
        };
    }

    protected function parameters(): array
    {
        return [
            'type' => $this->leaveRequest->leaveType->name,
            'dates' => DateRange::format($this->leaveRequest->start_date, $this->leaveRequest->end_date),
            'note' => (string) $this->leaveRequest->review_note,
        ];
    }

    protected function url(): string
    {
        return route('leave.index');
    }

    protected function icon(): string
    {
        return $this->approved() ? 'bi-calendar2-check' : 'bi-calendar2-x';
    }

    protected function context(): string
    {
        return $this->approved() ? 'success' : 'danger';
    }

    protected function subject(): string
    {
        return $this->approved() ? 'Your leave was approved' : 'Your leave was rejected';
    }

    protected function actionText(): string
    {
        return 'View my leave';
    }
}
