<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Demo departments, employees, leave and attendance for local development.
 */
class HrDemoSeeder extends Seeder
{
    public function run(): void
    {
        $departments = collect([
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'People, hiring and payroll.'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Accounts, budgeting and audit.'],
            ['name' => 'Engineering', 'code' => 'ENG', 'description' => 'Product development.'],
            ['name' => 'Sales', 'code' => 'SAL', 'description' => null],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => null],
        ])->map(fn (array $data) => Department::query()->firstOrCreate(['code' => $data['code']], $data));

        // The demo login gets an employee record so the self-service pages have data.
        $demoUser = User::query()->where('email', 'user@example.com')->first();

        $demo = Employee::factory()->for($departments[2])->create([
            'name' => $demoUser?->name ?? 'Demo User',
            'email' => $demoUser?->email ?? 'user@example.com',
            'job_title' => 'Software Engineer',
            'join_date' => now()->subYears(2),
        ]);
        $demo->user()->associate($demoUser)->save();

        $employees = collect([$demo]);

        foreach ($departments as $department) {
            $employees = $employees->merge(Employee::factory(4)->for($department)->create([
                'employment_type' => fn () => fake()->randomElement(EmploymentType::cases()),
            ]));
        }

        Employee::factory(2)->resigned()->for($departments[3])->create();

        $types = LeaveType::query()->get()->keyBy('name');
        $this->seedLeave($employees, $types->get('Annual leave'), $types->get('Sick leave'));
        $this->seedAttendance($employees);
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    protected function seedLeave($employees, LeaveType $annual, LeaveType $sick): void
    {
        foreach ($employees->random(8) as $index => $employee) {
            $start = today()->addWeekdays(fake()->numberBetween(-10, 20));
            $end = $start->copy()->addWeekdays(fake()->numberBetween(0, 3));

            $request = new LeaveRequest([
                'leave_type_id' => $index % 3 === 0 ? $sick->id : $annual->id,
                'start_date' => $start,
                'end_date' => $end,
                'days' => LeaveRequest::workingDays($start, $end),
                'reason' => fake()->randomElement(['Family trip', 'Medical appointment', 'Personal matters', 'Wedding', 'Fever']),
            ]);
            $request->employee()->associate($employee);
            $request->status = $start->isPast() ? LeaveStatus::Approved : fake()->randomElement([LeaveStatus::Pending, LeaveStatus::Approved]);
            $request->reviewed_at = $request->status === LeaveStatus::Approved ? now() : null;
            $request->save();
        }
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    protected function seedAttendance($employees): void
    {
        $days = CarbonPeriod::create(today()->subWeekdays(9), today());

        foreach ($days as $day) {
            if ($day->isWeekend()) {
                continue;
            }

            foreach ($employees as $employee) {
                // Roughly one absence in ten, and today only half of the team has clocked in so far.
                if (fake()->boolean(10) || ($day->isToday() && fake()->boolean(50))) {
                    continue;
                }

                $clockIn = Carbon::parse($day)->setTime(8, 30)->addMinutes(fake()->numberBetween(0, 50));

                $employee->attendances()->create([
                    'date' => $day,
                    'clock_in_at' => $clockIn,
                    'clock_out_at' => $day->isToday() ? null : $clockIn->copy()->addHours(9)->addMinutes(fake()->numberBetween(0, 40)),
                ]);
            }
        }
    }
}
