<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Contact;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('PO-####'),
            'contact_id' => Contact::factory()->state(['type' => ContactType::Supplier]),
            'order_date' => today()->toDateString(),
            'status' => PurchaseOrderStatus::Draft,
            'total' => '0.00',
        ];
    }
}
