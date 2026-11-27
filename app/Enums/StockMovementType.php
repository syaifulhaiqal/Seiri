<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => __('Received'),
            self::Issue => __('Issued'),
            self::Adjustment => __('Adjustment'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Receipt => 'success',
            self::Issue => 'warning',
            self::Adjustment => 'secondary',
        };
    }
}
