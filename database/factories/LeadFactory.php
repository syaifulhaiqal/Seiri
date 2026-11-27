<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Office fit-out', 'Annual maintenance', 'Website revamp', 'Payroll outsourcing']),
            'contact_name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->safeEmail(),
            'status' => LeadStatus::New,
            'value' => '5000.00',
        ];
    }
}
