<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([AdminUserSeeder::class, LeaveTypeSeeder::class]);

        if (! app()->isLocal()) {
            return;
        }

        // Demo data for local development only.
        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'user@example.com',
        ]);

        User::factory(15)->create();
        User::factory(4)->unverified()->create();

        $this->call(HrDemoSeeder::class);
    }
}
