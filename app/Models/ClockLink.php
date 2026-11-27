<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\ClockLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A clock-in link HR shares with all staff (e.g. in a WhatsApp group for work from home). Any active employee
 * who opens it and signs in can clock in or out. Renewing the token stops the old link working.
 */
#[Fillable(['name', 'expires_on', 'require_location'])]
#[Hidden(['token'])]
class ClockLink extends Model
{
    /** @use HasFactory<ClockLinkFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_on' => 'date',
            'require_location' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $link): void {
            $link->token ??= static::newToken();
        });
    }

    public static function newToken(): string
    {
        return Str::random(40);
    }

    /**
     * Links that have not passed their last day.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function usable(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('expires_on')->orWhereDate('expires_on', '>=', today()));
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isBefore(today());
    }

    public function url(): string
    {
        return route('clock_link.show', $this->token);
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
        return $this->name;
    }
}
