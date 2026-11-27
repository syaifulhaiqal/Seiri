<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-####'),
            'name' => fake()->randomElement(['A4 paper (ream)', 'Toner cartridge', 'USB-C cable', 'Office chair', 'Monitor 24"']),
            'unit' => 'pcs',
            'cost_price' => '20.00',
            'sale_price' => '35.00',
            'reorder_level' => 0,
        ];
    }
}
