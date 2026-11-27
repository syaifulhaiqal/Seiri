<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Concerns\Auditable;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sales invoice (type sales) or a supplier bill (type purchase).
 * `type`, `status`, totals and posting fields are set by the app, never mass-assigned.
 */
#[Fillable(['contact_id', 'issue_date', 'due_date', 'reference', 'tax_rate', 'notes'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'tax_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('date')->orderBy('id');
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function auditLabel(): string
    {
        return $this->number;
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function isOutstanding(): bool
    {
        return in_array($this->status, InvoiceStatus::outstanding(), true);
    }

    public function isOverdue(): bool
    {
        return $this->isOutstanding() && $this->due_date->lt(today());
    }

    public function balanceDueCents(): int
    {
        return Money::toCents($this->total) - Money::toCents($this->amount_paid);
    }

    #[Scope]
    protected function ofType(Builder $query, InvoiceType $type): void
    {
        $query->where('type', $type);
    }

    #[Scope]
    protected function outstanding(Builder $query): void
    {
        $query->whereIn('status', InvoiceStatus::outstanding());
    }

    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereIn('status', InvoiceStatus::outstanding())->whereDate('due_date', '<', today());
    }

    #[Scope]
    protected function search(Builder $query, null|string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->where('number', 'like', "%{$term}%")
            ->orWhere('reference', 'like', "%{$term}%")
            ->orWhereHas('contact', fn (Builder $query) => $query->where('name', 'like', "%{$term}%")));
    }
}
