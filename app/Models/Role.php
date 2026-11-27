<?php

namespace App\Models;

use App\Enums\Permission;
use App\Models\Concerns\Auditable;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An admin role, such as "HR manager": a name and the permissions that go with it.
 */
#[Fillable(['name', 'description', 'permissions'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => AsEnumCollection::of(Permission::class),
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'admin_role_id');
    }

    /**
     * Whether the role grants a permission, directly or through one that includes it.
     */
    public function grants(Permission $permission): bool
    {
        return $this->permissions->contains(fn (Permission $granted) => $granted === $permission || in_array($permission, $granted->includes(), true));
    }

    public function auditLabel(): string
    {
        return $this->name;
    }
}
