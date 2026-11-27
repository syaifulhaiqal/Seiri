<?php

namespace App\Models;

use App\Enums\ClockMethod;
use App\Models\Concerns\Auditable;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One employee's day. Clock times, method (office QR or remote) and where it happened are set by
 * App\Services\Clock only.
 */
#[Fillable(['date'])]
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
            'clock_in_method' => ClockMethod::class,
            'clock_out_method' => ClockMethod::class,
            'clock_in_latitude' => 'decimal:7',
            'clock_in_longitude' => 'decimal:7',
            'clock_out_latitude' => 'decimal:7',
            'clock_out_longitude' => 'decimal:7',
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

    /**
     * Google Maps link for where the employee was when they clocked in or out, if their browser shared it.
     */
    public function mapUrl(string $event = 'in'): null|string
    {
        $latitude = $this->{"clock_{$event}_latitude"};
        $longitude = $this->{"clock_{$event}_longitude"};

        return $latitude !== null && $longitude !== null
            ? 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude
            : null;
    }
}
