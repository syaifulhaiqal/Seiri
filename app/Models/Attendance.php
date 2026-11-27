<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['date', 'clock_in_at', 'clock_out_at'])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
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
        return ($this->employee?->name ?? '#' . $this->employee_id) . ' · ' . $this->date?->format('j M Y');
    }

    /**
     * Clocked in after the configured start of the working day.
     */
    public function isLate(): bool
    {
        if (! $this->clock_in_at) {
            return false;
        }

        [$hour, $minute] = array_map('intval', explode(':', config('hr.work_start')));

        return $this->clock_in_at->gt($this->date->copy()->setTime($hour, $minute));
    }

    /**
     * Hours between clock in and clock out, or null while still clocked in.
     */
    public function workedHours(): null|float
    {
        if (! $this->clock_in_at || ! $this->clock_out_at) {
            return null;
        }

        return round($this->clock_in_at->diffInMinutes($this->clock_out_at) / 60, 1);
    }
}
