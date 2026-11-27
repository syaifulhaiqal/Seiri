<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something the company buys, keeps in stock or sells. Stock on hand is the sum of its movements.
 */
#[Fillable(['sku', 'name', 'unit', 'cost_price', 'sale_price', 'reorder_level', 'is_active', 'description'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Auditable, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'unit' => 'pcs',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function auditLabel(): string
    {
        return "{$this->sku} {$this->name}";
    }

    /**
     * Stock on hand. Uses `on_hand` from withOnHand() when loaded.
     */
    public function onHand(): float
    {
        return (float) ($this->attributes['on_hand'] ?? $this->stockMovements()->sum('quantity'));
    }

    public function isLow(): bool
    {
        return (float) $this->reorder_level > 0 && $this->onHand() <= (float) $this->reorder_level;
    }

    /**
     * On hand × cost price, in sen.
     */
    public function stockValueCents(): int
    {
        return (int) round(Money::toCents($this->cost_price) * $this->onHand());
    }

    /**
     * "SKU · name", for pickers.
     */
    public function displayName(): string
    {
        return "{$this->sku} · {$this->name}";
    }

    #[Scope]
    protected function withOnHand(Builder $query): void
    {
        $query->withSum('stockMovements as on_hand', 'quantity');
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function search(Builder $query, null|string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query->where('sku', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"));
    }

    /**
     * Active products for line pickers: id => [label, cost, price, unit].
     *
     * @return array<int, array{label: string, name: string, cost: string, price: string, unit: string}>
     */
    public static function pickerOptions(): array
    {
        return self::query()->active()->orderBy('name')->get()
            ->mapWithKeys(fn (self $product) => [$product->id => [
                'label' => $product->displayName(),
                'name' => $product->name,
                'cost' => $product->cost_price,
                'price' => $product->sale_price,
                'unit' => $product->unit,
            ]])
            ->all();
    }
}
