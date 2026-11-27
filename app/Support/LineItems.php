<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Item lines of purchase requests, purchase orders and quotations: amounts worked out in sen.
 */
class LineItems
{
    /**
     * @param  array<int, array{product_id?: int|string|null, description: string, quantity: float|string, unit_price: float|string}>  $lines
     * @return Collection<int, array{product_id: int|null, description: string, quantity: float, unit_price: float, amount: string, sort: int}>
     */
    public static function build(array $lines): Collection
    {
        return collect($lines)->values()->map(fn (array $line, int $index) => [
            'product_id' => filled($line['product_id'] ?? null) ? (int) $line['product_id'] : null,
            'description' => $line['description'],
            'quantity' => round((float) $line['quantity'], 2),
            'unit_price' => round((float) $line['unit_price'], 2),
            'amount' => Money::fromCents((int) round(Money::toCents($line['unit_price']) * (float) $line['quantity'])),
            'sort' => $index,
        ]);
    }

    /**
     * Sum of the lines' amounts, in sen.
     *
     * @param  Collection<int, array{amount: string}>  $lines
     */
    public static function totalCents(Collection $lines): int
    {
        return $lines->sum(fn (array $line) => Money::toCents($line['amount']));
    }
}
