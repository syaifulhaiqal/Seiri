<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('now', '+2 months'))->startOfDay();

        while ($start->isWeekend()) {
            $start->addDay();
        }

        $end = $start->copy()->addWeekdays(fake()->numberBetween(0, 2));

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start,
            'end_date' => $end,
            'days' => LeaveRequest::workingDays($start, $end),
            'reason' => fake()->sentence(),
            'status' => LeaveStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }
}
