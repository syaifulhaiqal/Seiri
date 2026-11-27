<?php

namespace Database\Factories;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('PR-####'),
            'requested_by' => User::factory()->admin(),
            'purpose' => fake()->sentence(),
            'status' => PurchaseRequestStatus::Pending,
            'total' => '0.00',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PurchaseRequestStatus::Approved, 'reviewed_at' => now()]);
    }
}
