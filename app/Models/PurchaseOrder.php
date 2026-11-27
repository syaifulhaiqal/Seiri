<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasLineItems;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * An order to a supplier (PO-0001). Receiving it adds its products to stock; it can then become a draft bill.
 * `number`, `status`, `total`, `bill_id` and `received_at` are set by the app.
 */
#[Fillable(['contact_id', 'order_date', 'expected_date', 'notes'])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use Auditable, HasFactory, HasLineItems;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'status' => PurchaseOrderStatus::class,
            'total' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * The supplier.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * The supplier bill made from this order.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'bill_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphMany<StockMovement, $this>
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'source');
    }

    public function auditLabel(): string
    {
        return $this->number;
    }

    public function isDraft(): bool
    {
        return $this->status === PurchaseOrderStatus::Draft;
    }

    public function canBeReceived(): bool
    {
        return $this->status === PurchaseOrderStatus::Ordered;
    }

    public function canBeBilled(): bool
    {
        return in_array($this->status, [PurchaseOrderStatus::Ordered, PurchaseOrderStatus::Received], true) && $this->bill_id === null;
    }
}
