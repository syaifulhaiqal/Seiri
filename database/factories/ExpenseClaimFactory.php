<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Models\Account;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseClaim>
 */
class ExpenseClaimFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'CLM-' . fake()->unique()->numerify('####'),
            'employee_id' => Employee::factory(),
            'account_id' => Account::factory(),
            'date' => today()->subDays(fake()->numberBetween(1, 20)),
            'description' => fake()->randomElement(['Grab to client meeting', 'Printer ink', 'Team lunch', 'Parking at KLCC', 'Domain renewal']),
            'amount' => fake()->randomFloat(2, 10, 400),
            'status' => ClaimStatus::Pending,
        ];
    }
}
