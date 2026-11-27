<?php

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('first day of January this year', 'last day of December this year')->format('Y-m-d'),
            'name' => fake()->randomElement(['Hari Raya Aidilfitri', 'Chinese New Year', 'Deepavali', 'Wesak Day', 'Thaipusam']),
        ];
    }
}
