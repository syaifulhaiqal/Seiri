<?php

namespace Database\Factories;

use App\Models\ClockLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClockLink>
 */
class ClockLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Work from home', 'Client site', 'Field work']),
            'expires_on' => null,
            'require_location' => false,
        ];
    }
}
