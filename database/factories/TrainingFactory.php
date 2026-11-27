<?php

namespace Database\Factories;

use App\Enums\TrainingStatus;
use App\Models\Employee;
use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'title' => fake()->randomElement(['First aid', 'Excel for finance', 'Leadership basics', 'Fire safety']),
            'provider' => fake()->company(),
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->addDay()->toDateString(),
            'hours' => 14,
            'cost' => '800.00',
            'status' => TrainingStatus::Planned,
        ];
    }
}
