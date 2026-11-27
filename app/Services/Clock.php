<?php

namespace App\Services;

use App\Enums\ClockMethod;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Clocks an employee in or out for today, recording how (office QR or remote link) and where.
 * Errors are thrown as validation errors under `delete`, shown by the flash component.
 */
class Clock
{
    public function today(Employee $employee): null|Attendance
    {
        return $employee->attendances()->whereDate('date', today())->first();
    }

    /**
     * @param  array{latitude?: null|float|string, longitude?: null|float|string, work_location?: null|string}  $location
     *
     * @throws ValidationException
     */
    public function in(Employee $employee, ClockMethod $method, Request $request, array $location = []): Attendance
    {
        $attendance = $this->today($employee) ?? $employee->attendances()->make(['date' => today()]);

        if ($attendance->clock_in_at) {
            throw ValidationException::withMessages(['delete' => __('You already clocked in today at :time.', ['time' => $attendance->clock_in_at->format('g:i A')])]);
        }

        $attendance->clock_in_at = now();
        $attendance->work_location = $method === ClockMethod::Qr ? null : ($location['work_location'] ?? null);
        $this->stamp($attendance, 'in', $method, $request, $location);
        $attendance->save();

        return $attendance;
    }

    /**
     * @param  array{latitude?: null|float|string, longitude?: null|float|string}  $location
     *
     * @throws ValidationException
     */
    public function out(Employee $employee, ClockMethod $method, Request $request, array $location = []): Attendance
    {
        $attendance = $this->today($employee);

        if (! $attendance?->clock_in_at) {
            throw ValidationException::withMessages(['delete' => __('Clock in first.')]);
        }

        if ($attendance->clock_out_at) {
            throw ValidationException::withMessages(['delete' => __('You already clocked out today at :time.', ['time' => $attendance->clock_out_at->format('g:i A')])]);
        }

        $attendance->clock_out_at = now();
        $this->stamp($attendance, 'out', $method, $request, $location);
        $attendance->save();

        return $attendance;
    }

    /**
     * @param  array{latitude?: null|float|string, longitude?: null|float|string}  $location
     */
    protected function stamp(Attendance $attendance, string $event, ClockMethod $method, Request $request, array $location): void
    {
        $attendance->{"clock_{$event}_method"} = $method;
        $attendance->{"clock_{$event}_ip"} = $request->ip();
        $attendance->{"clock_{$event}_latitude"} = $location['latitude'] ?? null;
        $attendance->{"clock_{$event}_longitude"} = $location['longitude'] ?? null;
    }
}
