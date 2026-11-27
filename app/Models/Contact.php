<?php

namespace App\Models;

use App\Enums\ContactType;
use App\Enums\InvoiceType;
use App\Models\Concerns\Auditable;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer or supplier.
 */
#[Fillable(['name', 'type', 'email', 'phone_number', 'registration_no', 'address'])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
        ];
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    #[Scope]
    protected function usableFor(Builder $query, InvoiceType $type): void
    {
        $query->whereIn('type', $type->contactTypes());
    }

    #[Scope]
    protected function search(Builder $query, null|string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('registration_no', 'like', "%{$term}%"));
    }
}
