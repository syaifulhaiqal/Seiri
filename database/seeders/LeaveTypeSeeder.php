<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Default leave types. Existing rows are left as they are, so admins' changes survive a re-seed.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Annual leave', 'days_per_year' => 14, 'is_paid' => true],
            ['name' => 'Sick leave', 'days_per_year' => 14, 'is_paid' => true],
            ['name' => 'Emergency leave', 'days_per_year' => 3, 'is_paid' => true],
            ['name' => 'Unpaid leave', 'days_per_year' => 0, 'is_paid' => false],
        ] as $type) {
            LeaveType::query()->firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
