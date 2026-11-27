<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static null|string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->optional()->numerify('01#-### ####'),
            'role' => UserRole::User,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password123'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    /**
     * An admin. Give a role with ->for(Role::factory()->with(...), 'adminRole'); without one they can only sign in.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    /**
     * The owner of the system, with every permission.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SuperAdmin,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
