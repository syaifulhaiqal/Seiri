<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create (or restore) the default admin account from config/admin.php.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrNew(['email' => config('admin.email')]);

        if (! $admin->exists) {
            $admin->password = config('admin.password');
        }

        $admin->name = config('admin.name');
        $admin->role = UserRole::Admin;
        $admin->email_verified_at ??= now();
        $admin->save();
    }
}
