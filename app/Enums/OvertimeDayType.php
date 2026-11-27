<?php

namespace App\Enums;

use App\Support\HolidayCalendar;
use Carbon\CarbonInterface;

/**
 * Overtime rates under the Employment Act 1955 (s60A): times the hourly rate of pay.
 */
enum OvertimeDayType: string
{
    case Normal = 'normal';
    case RestDay = 'rest_day';
    case PublicHoliday = 'public_holiday';

    public static function for(CarbonInterface $date): self
    {
        return match (true) {
            app(HolidayCalendar::class)->isHoliday($date) => self::PublicHoliday,
            $date->isWeekend() => self::RestDay,
            default => self::Normal,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Normal => __('Working day'),
            self::RestDay => __('Rest day'),
            self::PublicHoliday => __('Public holiday'),
        };
    }

    public function rate(): float
    {
        return (float) config("hr.overtime.rates.{$this->value}");
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Normal => 'secondary',
            self::RestDay => 'info',
            self::PublicHoliday => 'primary',
        };
    }
}
