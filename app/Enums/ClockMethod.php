<?php

namespace App\Enums;

/**
 * How an employee clocked in or out.
 */
enum ClockMethod: string
{
    /** Scanned the rotating QR code shown in the office. */
    case Qr = 'qr';

    /** Clocked in from My attendance, for employees HR allows to work remotely. */
    case Remote = 'remote';

    /** Used a shared clock-in link from HR (work from home, on site). */
    case Link = 'link';

    public function label(): string
    {
        return match ($this) {
            self::Qr => __('Office QR'),
            self::Remote => __('Remote'),
            self::Link => __('Clock-in link'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Qr => 'bi-qr-code-scan',
            self::Remote => 'bi-house',
            self::Link => 'bi-link-45deg',
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Qr => 'primary',
            self::Remote => 'info',
            self::Link => 'success',
        };
    }
}
