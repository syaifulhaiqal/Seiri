<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => now()->subDays(2)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '20:00',
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
