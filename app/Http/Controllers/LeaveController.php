<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Http\Requests\LeaveApplyRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Self-service leave for the signed-in employee.
 */
class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('leave.index', [
            'employee' => $employee,
            'balances' => $employee?->leaveBalances(now()->year) ?? collect(),
            'leaveTypes' => LeaveType::query()->orderBy('name')->pluck('name', 'id'),
            'leaveRequests' => $employee
                ? $employee->leaveRequests()->with(['leaveType', 'reviewer'])->latest('start_date')->paginate(10)
                : null,
        ]);
    }

    public function store(LeaveApplyRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $leaveRequest = new LeaveRequest([
            ...$data,
            'days' => LeaveRequest::workingDays(Carbon::parse($data['start_date']), Carbon::parse($data['end_date'])),
        ]);
        $leaveRequest->employee()->associate($request->user()->employee);
        $leaveRequest->status = LeaveStatus::Pending;
        $leaveRequest->save();

        return to_route('leave.index')->with('status', trans_choice(
            'Leave request sent for :days working day. You will see the decision here.|Leave request sent for :days working days. You will see the decision here.',
            $leaveRequest->days,
            ['days' => $leaveRequest->days],
        ));
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->employee_id === $request->user()->employee?->id, 403);

        if (! $leaveRequest->isPending()) {
            return back()->withErrors(['delete' => __('Only pending requests can be cancelled. Ask HR to change approved leave.')]);
        }

        $leaveRequest->status = LeaveStatus::Cancelled;
        $leaveRequest->save();

        return to_route('leave.index')->with('status', __('Leave request cancelled.'));
    }
}
