<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()) . ' leave',
            'days_per_year' => 14,
            'is_paid' => true,
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'days_per_year' => 0,
            'is_paid' => false,
        ]);
    }
}
