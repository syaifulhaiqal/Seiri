<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = today();

        return [
            'employee_id' => Employee::factory(),
            'date' => $date,
            'clock_in_at' => $date->copy()->setTime(8, fake()->numberBetween(30, 59)),
            'clock_out_at' => $date->copy()->setTime(17, fake()->numberBetween(30, 59)),
        ];
    }
}
