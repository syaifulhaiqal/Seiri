<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\MovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An employee out of the office during working hours (client visit, bank, site), logged by themselves.
 */
#[Fillable(['date', 'time_out', 'time_back', 'destination', 'purpose'])]
class Movement extends Model
{
    /** @use HasFactory<MovementFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
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
        return __(':name to :destination', ['name' => $this->employee?->name ?? '#' . $this->employee_id, 'destination' => $this->destination]);
    }

    public function isOut(): bool
    {
        return $this->time_back === null;
    }

    public function formatTime(null|string $time): string
    {
        return $time ? Carbon::parse($time)->format('g:i A') : '—';
    }
}
