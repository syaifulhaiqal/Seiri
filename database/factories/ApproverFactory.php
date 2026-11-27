<?php

namespace Database\Factories;

use App\Enums\ApprovalType;
use App\Models\Approver;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Approver>
 */
class ApproverFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => ApprovalType::Leave,
            'level' => 1,
            'approver_id' => User::factory(),
        ];
    }
}
