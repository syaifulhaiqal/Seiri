<?php

namespace App\Enums;

/**
 * A fixed amount added to or taken from an employee's pay every month.
 */
enum PayItemType: string
{
    case Allowance = 'allowance';
    case Deduction = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::Allowance => __('Allowance'),
            self::Deduction => __('Deduction'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Allowance => 'success',
            self::Deduction => 'danger',
        };
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
