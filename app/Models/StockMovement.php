<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Stock in (positive quantity) or out (negative), from a received purchase order or a manual adjustment.
 * Quantities only: stock is not posted to the ledger.
 */
#[Fillable(['date', 'quantity', 'unit_cost', 'type', 'note'])]
class StockMovement extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'type' => StockMovementType::class,
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The purchase order it came from, if any.
     *
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function auditLabel(): string
    {
        return __(':quantity × :product', ['quantity' => (float) $this->quantity, 'product' => $this->product?->sku ?? '#' . $this->product_id]);
    }
}
