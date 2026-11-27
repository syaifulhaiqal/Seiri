<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeded records are not audit-logged, so the audit trail only shows what people do.
        AuditLog::withoutRecording(fn () => $this->seed());
    }

    protected function seed(): void
    {
        $this->call([AdminUserSeeder::class, RoleSeeder::class, LeaveTypeSeeder::class, HolidaySeeder::class, ChartOfAccountsSeeder::class]);

        if (! app()->isLocal()) {
            return;
        }

        // Demo data for local development only.
        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'user@example.com',
        ]);

        // One admin per starting role, to try the permissions: hr@, payroll@, finance@ and auditor@example.com.
        foreach (['hr' => 'HR manager', 'payroll' => 'Payroll officer', 'finance' => 'Finance manager', 'auditor' => 'Auditor'] as $login => $roleName) {
            User::factory()->admin()->for(Role::query()->where('name', $roleName)->sole(), 'adminRole')->create([
                'name' => $roleName . ' (demo)',
                'email' => "{$login}@example.com",
            ]);
        }

        User::factory(15)->create();
        User::factory(4)->unverified()->create();

        $this->call([HrDemoSeeder::class, FinanceDemoSeeder::class, PayrollDemoSeeder::class]);
    }
}
