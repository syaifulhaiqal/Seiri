<?php

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'permissions' => [],
        ];
    }

    /**
     * @param  array<int, Permission>  $permissions
     */
    public function with(Permission ...$permissions): static
    {
        return $this->state(fn (array $attributes) => ['permissions' => $permissions]);
    }
}
