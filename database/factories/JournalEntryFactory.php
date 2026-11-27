<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Bare entries without lines. Post real entries through App\Services\Ledger.
 *
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'JE-' . fake()->unique()->numerify('######'),
            'date' => today(),
            'description' => fake()->sentence(3),
        ];
    }
}
