<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Enums\QuotationStatus;
use App\Models\Contact;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('QT-####'),
            'contact_id' => Contact::factory()->state(['type' => ContactType::Customer]),
            'issue_date' => today()->toDateString(),
            'valid_until' => today()->addDays(30)->toDateString(),
            'status' => QuotationStatus::Draft,
        ];
    }
}
