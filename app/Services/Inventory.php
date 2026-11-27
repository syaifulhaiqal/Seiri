<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Moving stock in and out. Quantities only: stock value is not posted to the ledger.
 */
class Inventory
{
    /**
     * Record a movement. A positive quantity adds stock, a negative one takes it out.
     */
    public function move(
        Product $product,
        float $quantity,
        StockMovementType $type,
        string $date,
        null|User $by,
        null|string $note = null,
        null|string $unitCost = null,
        null|Model $source = null,
    ): StockMovement {
        $movement = new StockMovement([
            'date' => $date,
            'quantity' => round($quantity, 2),
            'unit_cost' => $unitCost,
            'type' => $type,
            'note' => $note,
        ]);
        $movement->product()->associate($product);
        $movement->creator()->associate($by);

        if ($source) {
            $movement->source()->associate($source);
        }

        $movement->save();

        return $movement;
    }
}
