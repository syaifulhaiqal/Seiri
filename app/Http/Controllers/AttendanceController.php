<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Self-service clock in / out for the signed-in employee.
 */
class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('attendance.index', [
            'employee' => $employee,
            'today' => $employee ? $this->today($employee) : null,
            'attendances' => $employee
                ? $employee->attendances()->whereDate('date', '>=', today()->subDays(30))->latest('date')->get()
                : collect(),
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $employee = $this->activeEmployee($request);
        $attendance = $this->today($employee) ?? $employee->attendances()->make(['date' => today()]);

        if ($attendance->clock_in_at) {
            return back()->withErrors(['delete' => __('You already clocked in today at :time.', ['time' => $attendance->clock_in_at->format('g:i A')])]);
        }

        $attendance->clock_in_at = now();
        $attendance->save();

        return to_route('attendance.index')->with('status', __('Clocked in at :time.', ['time' => $attendance->clock_in_at->format('g:i A')]));
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $attendance = $this->today($this->activeEmployee($request));

        if (! $attendance?->clock_in_at) {
            return back()->withErrors(['delete' => __('Clock in first.')]);
        }

        if ($attendance->clock_out_at) {
            return back()->withErrors(['delete' => __('You already clocked out today at :time.', ['time' => $attendance->clock_out_at->format('g:i A')])]);
        }

        $attendance->clock_out_at = now();
        $attendance->save();

        return to_route('attendance.index')->with('status', __('Clocked out at :time. You worked :hours hours today.', [
            'time' => $attendance->clock_out_at->format('g:i A'),
            'hours' => $attendance->workedHours(),
        ]));
    }

    protected function activeEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        abort_unless($employee?->isActive(), 403);

        return $employee;
    }

    protected function today(Employee $employee): null|Attendance
    {
        return $employee->attendances()->whereDate('date', today())->first();
    }
}
