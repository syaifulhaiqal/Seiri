<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => ContactType::Customer,
            'email' => fake()->companyEmail(),
            'phone_number' => fake()->numerify('03-#### ####'),
        ];
    }

    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ContactType::Supplier]);
    }
}
