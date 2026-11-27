<?php

namespace App\Notifications;

use App\Enums\LeaveStatus;
use App\Models\OvertimeRequest;

/**
 * To the employee: their overtime was approved (and will be paid in the next payroll) or rejected.
 */
class OvertimeReviewed extends AppNotification
{
    public function __construct(public OvertimeRequest $overtime)
    {
        parent::__construct();
    }

    protected function approved(): bool
    {
        return $this->overtime->status === LeaveStatus::Approved;
    }

    protected function message(): string
    {
        return match (true) {
            $this->approved() => 'Your :hours h overtime on :date was approved. It will be paid in the next payroll.',
            filled($this->overtime->review_note) => 'Your :hours h overtime on :date was rejected: :note',
            default => 'Your :hours h overtime on :date was rejected.',
        };
    }

    protected function parameters(): array
    {
        return [
            'hours' => (float) $this->overtime->hours,
            'date' => $this->overtime->date->format('j M Y'),
            'note' => (string) $this->overtime->review_note,
        ];
    }

    protected function url(): string
    {
        return route('overtime.index');
    }

    protected function icon(): string
    {
        return $this->approved() ? 'bi-hourglass-bottom' : 'bi-x-circle';
    }

    protected function context(): string
    {
        return $this->approved() ? 'success' : 'danger';
    }

    protected function subject(): string
    {
        return $this->approved() ? 'Your overtime was approved' : 'Your overtime was rejected';
    }

    protected function actionText(): string
    {
        return 'View my overtime';
    }
}
