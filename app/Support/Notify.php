<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Who gets told about what: admins whose role can act on it, or the employee it is about.
 */
class Notify
{
    /**
     * Every active admin with the permission, except whoever caused it.
     */
    public static function admins(Permission $permission, AppNotification $notification): void
    {
        Notification::send(static::adminsWith($permission), $notification);
    }

    /**
     * The employee's login, if they have one.
     */
    public static function employee(Employee $employee, AppNotification $notification): void
    {
        $employee->user?->notify($notification);
    }

    /**
     * @return Collection<int, User>
     */
    public static function adminsWith(Permission $permission): Collection
    {
        return User::query()
            ->with('adminRole')
            ->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])
            ->whereNotNull('email_verified_at')
            ->when(auth()->id(), fn ($query, int $id) => $query->whereKeyNot($id))
            ->get()
            ->filter(fn (User $user) => $user->hasPermission($permission))
            ->values();
    }
}
