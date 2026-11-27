<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\ClockMethod;
use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Who was in on one day: every active employee with their clock times, how and where they clocked in
     * (office QR or remote, with location), and status.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(AttendanceStatus::class)],
            'method' => ['nullable', Rule::enum(ClockMethod::class)],
        ]);

        $date = Carbon::parse($filters['date'] ?? today())->startOfDay();

        $employees = Employee::query()
            ->active()
            ->whereDate('join_date', '<=', $date)
            ->when($filters['department'] ?? null, fn ($query, int $id) => $query->where('department_id', $id))
            ->when($filters['method'] ?? null, fn ($query, string $method) => $query->whereHas('attendances', fn ($query) => $query
                ->whereDate('date', $date)
                ->where(fn ($query) => $query->where('clock_in_method', $method)->orWhere('clock_out_method', $method))))
            ->with(['department', 'attendances' => fn ($query) => $query->whereDate('date', $date)])
            ->orderBy('name')
            ->get();

        $onLeave = LeaveRequest::query()
            ->where('status', LeaveStatus::Approved)
            ->overlapping($date, $date)
            ->whereIn('employee_id', $employees->modelKeys())
            ->pluck('employee_id')
            ->flip();

        $rows = $employees->map(fn (Employee $employee) => [
            'employee' => $employee,
            'attendance' => $attendance = $employee->attendances->first(),
            'status' => AttendanceStatus::resolve($date, $attendance, $onLeave->has($employee->id)),
        ]);

        $counts = collect(AttendanceStatus::cases())
            ->mapWithKeys(fn (AttendanceStatus $status) => [$status->value => $rows->where('status', $status)->count()])
            // The holiday card shows only on a holiday.
            ->reject(fn (int $count, string $status) => $status === AttendanceStatus::Holiday->value && $count === 0);

        if ($filters['status'] ?? null) {
            $rows = $rows->where('status', AttendanceStatus::from($filters['status']))->values();
        }

        return view('admin.attendance.index', [
            'date' => $date,
            'rows' => $rows,
            'counts' => $counts,
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
