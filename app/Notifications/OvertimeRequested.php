<?php

namespace App\Notifications;

use App\Models\OvertimeRequest;

/**
 * To admins who review overtime: a new claim is waiting (sent when the employee has no approval chain).
 */
class OvertimeRequested extends AppNotification
{
    public function __construct(public OvertimeRequest $overtime)
    {
        parent::__construct();
    }

    protected function message(): string
    {
        return ':name claimed :hours h overtime on :date.';
    }

    protected function parameters(): array
    {
        return [
            'name' => $this->overtime->employee->name,
            'hours' => (float) $this->overtime->hours,
            'date' => $this->overtime->date->format('j M Y'),
        ];
    }

    protected function url(): string
    {
        return route('admin.overtime.show', $this->overtime);
    }

    protected function icon(): string
    {
        return 'bi-hourglass-split';
    }

    protected function context(): string
    {
        return 'warning';
    }

    protected function subject(): string
    {
        return 'Overtime claim from :name';
    }

    protected function actionText(): string
    {
        return 'Review overtime';
    }
}
