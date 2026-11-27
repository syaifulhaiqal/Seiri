<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasLineItems;
use Database\Factories\QuotationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A price quote to a customer (QT-0001). Once accepted it becomes a draft sales invoice.
 * `number`, `status`, totals and `invoice_id` are set by the app.
 */
#[Fillable(['contact_id', 'issue_date', 'valid_until', 'tax_rate', 'notes'])]
class Quotation extends Model
{
    /** @use HasFactory<QuotationFactory> */
    use Auditable, HasFactory, HasLineItems;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'valid_until' => 'date',
            'status' => QuotationStatus::class,
            'tax_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<QuotationLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * The customer.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * The sales invoice made from this quotation.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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
        return $this->number;
    }

    public function isDraft(): bool
    {
        return $this->status === QuotationStatus::Draft;
    }

    public function isExpired(): bool
    {
        return $this->status === QuotationStatus::Sent && $this->valid_until->lt(today());
    }

    public function canBeInvoiced(): bool
    {
        return in_array($this->status, [QuotationStatus::Sent, QuotationStatus::Accepted], true) && $this->invoice_id === null;
    }
}
