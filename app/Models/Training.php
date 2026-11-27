<?php

namespace App\Models;

use App\Enums\TrainingStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A course or training an employee attends (HRDF, certification, in-house), kept by HR.
 */
#[Fillable(['title', 'provider', 'start_date', 'end_date', 'hours', 'cost', 'status', 'notes'])]
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'hours' => 'decimal:1',
            'cost' => 'decimal:2',
            'status' => TrainingStatus::class,
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
        return $this->title . ' · ' . ($this->employee?->name ?? '#' . $this->employee_id);
    }
}
