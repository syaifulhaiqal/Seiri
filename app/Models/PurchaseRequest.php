<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasLineItems;
use Database\Factories\PurchaseRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A request to buy something (PR-0001), approved by someone with procurement.approve before it is ordered.
 * `number`, `status`, `total` and review fields are set by the app.
 */
#[Fillable(['needed_by', 'purpose'])]
class PurchaseRequest extends Model
{
    /** @use HasFactory<PurchaseRequestFactory> */
    use Auditable, HasFactory, HasLineItems;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'needed_by' => 'date',
            'status' => PurchaseRequestStatus::class,
            'total' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PurchaseRequestLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequestLine::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function auditLabel(): string
    {
        return $this->number;
    }

    public function isPending(): bool
    {
        return $this->status === PurchaseRequestStatus::Pending;
    }

    public function canBeOrdered(): bool
    {
        return $this->status === PurchaseRequestStatus::Approved;
    }
}
