<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveReviewRequest;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Services\ApprovalFlow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(LeaveStatus::class)],
        ]);

        $leaveRequests = LeaveRequest::query()
            ->with(['employee.department', 'leaveType'])
            ->whereHas('employee', fn (Builder $query) => $query
                ->search($filters['search'] ?? null)
                ->when($filters['department'] ?? null, fn ($query, int $id) => $query->where('department_id', $id)))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            // Pending first, then the most recent.
            ->orderByRaw('case when status = ? then 0 else 1 end', [LeaveStatus::Pending->value])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.leave-requests.index', [
            'leaveRequests' => $leaveRequests,
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'pendingCount' => LeaveRequest::query()->where('status', LeaveStatus::Pending)->count(),
        ]);
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $leaveRequest->load(['employee.department', 'leaveType', 'reviewer', 'approvals.approver']);

        return view('admin.leave-requests.show', [
            'leaveRequest' => $leaveRequest,
            'balances' => $leaveRequest->employee->leaveBalances($leaveRequest->start_date->year),
            'overlapping' => $leaveRequest->employee->department_id
                ? LeaveRequest::query()
                    ->with('employee')
                    ->where('status', LeaveStatus::Approved)
                    ->whereKeyNot($leaveRequest->id)
                    ->overlapping($leaveRequest->start_date, $leaveRequest->end_date)
                    ->whereHas('employee', fn (Builder $query) => $query->where('department_id', $leaveRequest->employee->department_id))
                    ->get()
                : collect(),
        ]);
    }

    public function review(LeaveReviewRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        if (! $leaveRequest->isPending()) {
            return back()->withErrors(['review' => __('This request has already been :status.', ['status' => strtolower($leaveRequest->status->label())])]);
        }

        $approve = $request->validated('decision') === 'approve';

        // HR's decision is final, even if approvers in the employee's chain have not decided yet.
        DB::transaction(function () use ($approve, $leaveRequest, $request): void {
            app(ApprovalFlow::class)->close($leaveRequest);

            $approve
                ? $leaveRequest->approveFinally($request->user(), $request->validated('review_note'))
                : $leaveRequest->rejectFinally($request->user(), $request->validated('review_note'));
        });

        return to_route('admin.leave_requests.index', ['status' => LeaveStatus::Pending->value])->with('status', $approve
            ? __('Leave for :name approved.', ['name' => $leaveRequest->employee->name])
            : __('Leave for :name rejected.', ['name' => $leaveRequest->employee->name]));
    }
}
