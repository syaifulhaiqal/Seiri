<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->optional()->numerify('01#-### ####'),
            'job_title' => fake()->jobTitle(),
            'employment_type' => EmploymentType::Permanent,
            'status' => EmployeeStatus::Active,
            // Relative to now() so it follows Carbon::setTestNow() in tests.
            'join_date' => now()->subDays(fake()->numberBetween(30, 1800))->startOfDay(),
            'basic_salary' => fake()->randomElement([2800, 3200, 3800, 4500, 5200, 6500, 8000]),
            'monthly_pcb' => 0,
            'bank_name' => fake()->randomElement(['Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank']),
            'bank_account_no' => fake()->numerify('1#### #### ###'),
            'date_of_birth' => fake()->optional()->passthrough(now()->subDays(fake()->numberBetween(7300, 20000))->startOfDay()),
        ];
    }

    public function resigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatus::Resigned,
            'end_date' => now()->subMonth(),
        ]);
    }
}
