<?php

namespace App\Support;

/**
 * Money is stored as decimal(…, 2) and calculated in whole sen (integers), so totals never drift.
 */
class Money
{
    public static function toCents(null|float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * "RM 1,234.50", or "-RM 1,234.50" for negative amounts.
     */
    public static function format(null|float|int|string $amount, bool $withCurrency = true): string
    {
        $cents = static::toCents($amount);
        $number = number_format(abs($cents) / 100, 2);
        $text = $withCurrency ? config('finance.currency') . ' ' . $number : $number;

        return $cents < 0 ? '-' . $text : $text;
    }
}
