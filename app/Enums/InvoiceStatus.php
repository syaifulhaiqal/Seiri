<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Open => __('Awaiting payment'),
            self::PartiallyPaid => __('Partly paid'),
            self::Paid => __('Paid'),
            self::Void => __('Void'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Open => 'warning',
            self::PartiallyPaid => 'info',
            self::Paid => 'success',
            self::Void => 'danger',
        };
    }

    /**
     * Issued and not yet fully paid.
     *
     * @return array<int, self>
     */
    public static function outstanding(): array
    {
        return [self::Open, self::PartiallyPaid];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
