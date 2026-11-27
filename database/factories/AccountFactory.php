<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => (string) fake()->unique()->numberBetween(7000, 7999),
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'type' => AccountType::Expense,
            'is_active' => true,
        ];
    }
}
