<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Models\Concerns\Auditable;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An account in the chart of accounts. `system_key` marks the accounts the app posts to itself.
 */
#[Fillable(['code', 'name', 'type', 'description', 'is_active'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * The account the app uses for a job, e.g. Account::system('bank').
     */
    public static function system(string $key): self
    {
        return static::query()->where('system_key', $key)->firstOrFail();
    }

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function isSystem(): bool
    {
        return $this->system_key !== null;
    }

    public function auditLabel(): string
    {
        return "{$this->code} {$this->name}";
    }

    /**
     * "6100 · Rent", for select boxes.
     */
    public function displayName(): string
    {
        return "{$this->code} · {$this->name}";
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function ofType(Builder $query, AccountType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Bank and cash accounts that money can be paid from or into.
     */
    #[Scope]
    protected function cashAndBank(Builder $query): void
    {
        $query->where('type', AccountType::Asset)
            ->where(fn (Builder $query) => $query->whereIn('system_key', ['bank', 'cash'])->orWhere('code', 'like', '10%'));
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
            ->orWhere('code', 'like', "{$term}%"));
    }
}
