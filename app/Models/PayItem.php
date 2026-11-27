<?php

namespace App\Models;

use App\Enums\PayItemType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A fixed monthly allowance or deduction for one employee, e.g. "Transport allowance".
 */
#[Fillable(['name', 'type', 'amount'])]
class PayItem extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PayItemType::class,
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function auditLabel(): string
    {
        return "{$this->name} · " . ($this->employee?->name ?? '#' . $this->employee_id);
    }
}
