<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Movement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movement>
 */
class MovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => today()->toDateString(),
            'time_out' => '10:00',
            'time_back' => null,
            'destination' => fake()->company(),
            'purpose' => fake()->sentence(),
        ];
    }
}
