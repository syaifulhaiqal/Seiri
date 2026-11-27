<?php

namespace App\Models\Concerns;

use App\Support\LineItems;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A document with item lines (purchase request, purchase order, quotation). The document must be saved first.
 */
trait HasLineItems
{
    abstract public function lines(): HasMany;

    /**
     * Replace every line and return the new subtotal in sen.
     *
     * @param  array<int, array{product_id?: int|string|null, description: string, quantity: float|string, unit_price: float|string}>  $lines
     */
    public function replaceLines(array $lines): int
    {
        $built = LineItems::build($lines);

        $this->lines()->delete();
        $this->lines()->createMany($built->all());
        $this->unsetRelation('lines');

        return LineItems::totalCents($built);
    }

    /**
     * The lines as form rows, for editing or copying into another document.
     *
     * @return array<int, array{product_id: int|null, description: string, quantity: float, unit_price: string}>
     */
    public function lineRows(): array
    {
        return $this->lines->map(fn ($line) => [
            'product_id' => $line->product_id,
            'description' => $line->description,
            'quantity' => (float) $line->quantity,
            'unit_price' => $line->unit_price,
        ])->values()->all();
    }
}
