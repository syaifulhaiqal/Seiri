<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Contact;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A draft with no lines. Use App\Services\InvoicePosting to add lines and issue it.
 *
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => InvoiceType::Sales,
            'number' => 'INV-' . fake()->unique()->numerify('####'),
            'contact_id' => Contact::factory(),
            'issue_date' => today(),
            'due_date' => today()->addDays(30),
            'status' => InvoiceStatus::Draft,
        ];
    }
}
