<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        return view('dashboard', [
            'user' => $user,
            'stats' => $isAdmin ? $this->userStats() : null,
            'hrStats' => $isAdmin ? $this->hrStats() : null,
            'pendingLeave' => $isAdmin
                ? LeaveRequest::query()->with(['employee', 'leaveType'])->where('status', LeaveStatus::Pending)->oldest()->take(5)->get()
                : collect(),
            'employee' => $isAdmin ? null : $user->employee,
            'todayAttendance' => $isAdmin ? null : $user->employee?->attendances()->whereDate('date', today())->first(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    protected function userStats(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()->whereNotNull('email_verified_at')->count(),
            'inactive' => User::query()->whereNull('email_verified_at')->count(),
            'admins' => User::query()->where('role', UserRole::Admin)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function hrStats(): array
    {
        return [
            'employees' => Employee::query()->active()->count(),
            'present' => Attendance::query()
                ->whereDate('date', today())
                ->whereNotNull('clock_in_at')
                ->whereHas('employee', fn (Builder $query) => $query->active())
                ->count(),
            'on_leave' => LeaveRequest::query()
                ->where('status', LeaveStatus::Approved)
                ->overlapping(today(), today())
                ->whereHas('employee', fn (Builder $query) => $query->active())
                ->distinct('employee_id')
                ->count('employee_id'),
            'pending_leave' => LeaveRequest::query()->where('status', LeaveStatus::Pending)->count(),
        ];
    }
}
