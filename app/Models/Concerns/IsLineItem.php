<?php

namespace App\Models\Concerns;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One item line of a document with HasLineItems. The line keeps its own description and price, so editing the product later does not change it.
 */
trait IsLineItem
{
    public function initializeIsLineItem(): void
    {
        $this->timestamps = false;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
