<?php

namespace App\Http\Controllers;

use App\Enums\ClockMethod;
use App\Http\Requests\RemoteClockRequest;
use App\Models\Employee;
use App\Services\Clock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Self-service clock in / out for the signed-in employee: by scanning the office QR code, or with the
 * remote link (this page) when HR allows them to work remotely.
 */
class AttendanceController extends Controller
{
    public function __construct(protected Clock $clock) {}

    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('attendance.index', [
            'employee' => $employee,
            'today' => $employee ? $this->clock->today($employee) : null,
            'attendances' => $employee
                ? $employee->attendances()->whereDate('date', '>=', today()->subDays(30))->latest('date')->get()
                : collect(),
        ]);
    }

    public function clockIn(RemoteClockRequest $request): RedirectResponse
    {
        $attendance = $this->clock->in($this->remoteEmployee($request), ClockMethod::Remote, $request, $request->location());

        return to_route('attendance.index')->with('status', __('Clocked in at :time.', ['time' => $attendance->clock_in_at->format('g:i A')]));
    }

    public function clockOut(RemoteClockRequest $request): RedirectResponse
    {
        $attendance = $this->clock->out($this->remoteEmployee($request), ClockMethod::Remote, $request, $request->location());

        return $this->clockedOut($attendance->clock_out_at->format('g:i A'), $attendance->workedHours());
    }

    /**
     * Opened by scanning the office QR code: a signed link that works for a few minutes. Remembers the scan
     * for this session, then asks the employee to confirm.
     */
    public function scan(Request $request): RedirectResponse|View
    {
        if (! $request->hasValidSignature()) {
            return to_route('attendance.index')->withErrors(['delete' => __('This QR code has expired. Scan the code on the office screen again.')]);
        }

        $employee = $this->activeEmployee($request);
        $request->session()->put('attendance_qr_scanned_at', now()->timestamp);

        return view('attendance.scan', [
            'employee' => $employee,
            'today' => $this->clock->today($employee),
        ]);
    }

    /**
     * Clocks in, or out if already in, after a QR scan in this session.
     */
    public function qr(Request $request): RedirectResponse
    {
        $employee = $this->activeEmployee($request);
        $scannedAt = $request->session()->pull('attendance_qr_scanned_at');

        if (! $scannedAt || Date::createFromTimestamp($scannedAt)->addMinutes(config('hr.qr_valid_minutes'))->isPast()) {
            return to_route('attendance.index')->withErrors(['delete' => __('This QR code has expired. Scan the code on the office screen again.')]);
        }

        if (config('hr.office_ips') && ! in_array($request->ip(), config('hr.office_ips'), true)) {
            return to_route('attendance.index')->withErrors(['delete' => __('The office QR code only works on the office network. Connect to the office Wi-Fi and scan again.')]);
        }

        $today = $this->clock->today($employee);

        if (! $today?->clock_in_at) {
            $attendance = $this->clock->in($employee, ClockMethod::Qr, $request);

            return to_route('attendance.index')->with('status', __('Clocked in at :time.', ['time' => $attendance->clock_in_at->format('g:i A')]));
        }

        $attendance = $this->clock->out($employee, ClockMethod::Qr, $request);

        return $this->clockedOut($attendance->clock_out_at->format('g:i A'), $attendance->workedHours());
    }

    protected function clockedOut(string $time, null|float $hours): RedirectResponse
    {
        return to_route('attendance.index')->with('status', __('Clocked out at :time. You worked :hours hours today.', [
            'time' => $time,
            'hours' => $hours,
        ]));
    }

    protected function activeEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        abort_unless($employee?->isActive(), 403);

        return $employee;
    }

    /**
     * Only employees HR allows to work remotely can clock in without the office QR code.
     *
     * @throws ValidationException
     */
    protected function remoteEmployee(Request $request): Employee
    {
        $employee = $this->activeEmployee($request);

        if (! $employee->can_work_remotely) {
            throw ValidationException::withMessages(['delete' => __('Scan the QR code on the office screen to clock in. Ask HR if you need to clock in remotely.')]);
        }

        return $employee;
    }
}
