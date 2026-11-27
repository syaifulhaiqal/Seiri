<?php

namespace App\Enums;

use App\Models\Attendance;
use Illuminate\Support\Carbon;

/**
 * Not stored: worked out from the attendance record and approved leave for a given day.
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case OnLeave = 'on_leave';
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Present => __('Present'),
            self::Late => __('Late'),
            self::OnLeave => __('On leave'),
            self::Absent => __('Absent'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Late => 'warning',
            self::OnLeave => 'info',
            self::Absent => 'danger',
        };
    }

    /**
     * Status for one employee on one day. Null when there is nothing to report yet:
     * a future day, or a weekend with no clock-in.
     */
    public static function resolve(Carbon $date, null|Attendance $attendance, bool $onLeave): null|self
    {
        return match (true) {
            $attendance?->clock_in_at !== null => $attendance->isLate() ? self::Late : self::Present,
            $onLeave => self::OnLeave,
            $date->isFuture(), $date->isWeekend() => null,
            default => self::Absent,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
