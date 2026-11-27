<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * `role` is deliberately not fillable, so a request can never promote itself to admin.
 * Set it explicitly (see Admin\UserController and the seeders).
 *
 * `locale` is the language the user picked (en or ms); emails to them are sent in it too.
 */
#[Fillable(['name', 'email', 'phone_number', 'password', 'locale', 'email_notifications'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable;

    /**
     * Same as the column default, so a user created in this request is emailed too.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'email_notifications' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'email_notifications' => 'boolean',
        ];
    }

    public function preferredLocale(): null|string
    {
        return $this->locale;
    }

    /**
     * The HR record linked to this login, if any.
     *
     * @return HasOne<Employee, $this>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * The admin role that decides which modules this admin can use. Super admins do not need one.
     *
     * @return BelongsTo<Role, $this>
     */
    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'admin_role_id');
    }

    /**
     * Can sign in to the admin area: admins and the super admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    /**
     * The owner of the system (e.g. the CEO): every permission, and the only one who manages roles.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function hasPermission(Permission $permission): bool
    {
        return match ($this->role) {
            UserRole::SuperAdmin => true,
            UserRole::Admin => (bool) $this->adminRole?->grants($permission),
            default => false,
        };
    }

    /**
     * Is in someone's approval chain, or still has steps they decided or must decide.
     */
    public function isApprover(): bool
    {
        return once(fn () => Approver::query()->where('approver_id', $this->id)->exists()
            || Approval::query()->where('approver_id', $this->id)->exists());
    }

    /**
     * Search by name, email or phone number.
     */
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
            ->orWhere('phone_number', 'like', "%{$term}%"));
    }
}
