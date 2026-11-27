<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sales opportunity. Converting it creates a customer, who can then be quoted and invoiced.
 */
#[Fillable(['title', 'contact_name', 'company', 'email', 'phone_number', 'source', 'status', 'value', 'expected_close', 'owner_id', 'notes'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'value' => 'decimal:2',
            'expected_close' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The customer this lead became.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return HasMany<LeadActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('happened_at')->latest('id');
    }

    /**
     * @return HasMany<Quotation, $this>
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function auditLabel(): string
    {
        return $this->title;
    }

    #[Scope]
    protected function search(Builder $query, null|string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->where('title', 'like', "%{$term}%")
            ->orWhere('contact_name', 'like', "%{$term}%")
            ->orWhere('company', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%"));
    }
}
