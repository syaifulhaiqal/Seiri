<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveReviewRequest;
use App\Models\Department;
use App\Models\OvertimeRequest;
use App\Services\ApprovalFlow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * HR reviewing overtime claims. Approved claims go into the next payroll run.
 */
class OvertimeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(LeaveStatus::class)],
        ]);

        $requests = OvertimeRequest::query()
            ->with(['employee.department', 'payslip.payrollRun'])
            ->whereHas('employee', fn (Builder $query) => $query
                ->search($filters['search'] ?? null)
                ->when($filters['department'] ?? null, fn ($query, int $id) => $query->where('department_id', $id)))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByRaw('case when status = ? then 0 else 1 end', [LeaveStatus::Pending->value])
            ->latest('date')
            ->paginate(15)
            ->withQueryString();

        return view('admin.overtime.index', [
            'requests' => $requests,
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'pendingCount' => OvertimeRequest::query()->where('status', LeaveStatus::Pending)->count(),
        ]);
    }

    public function show(OvertimeRequest $overtime): View
    {
        $overtime->load(['employee.department', 'reviewer', 'payslip.payrollRun', 'approvals.approver']);

        return view('admin.overtime.show', [
            'overtime' => $overtime,
            'attendance' => $overtime->employee->attendances()->whereDate('date', $overtime->date)->first(),
            'monthHours' => (float) $overtime->employee->overtimeRequests()
                ->where('status', LeaveStatus::Approved)
                ->whereYear('date', $overtime->date->year)
                ->whereMonth('date', $overtime->date->month)
                ->sum('hours'),
        ]);
    }

    public function review(LeaveReviewRequest $request, OvertimeRequest $overtime): RedirectResponse
    {
        if (! $overtime->isPending()) {
            return back()->withErrors(['review' => __('This request has already been :status.', ['status' => strtolower($overtime->status->label())])]);
        }

        $approve = $request->validated('decision') === 'approve';

        DB::transaction(function () use ($approve, $overtime, $request): void {
            app(ApprovalFlow::class)->close($overtime);

            $approve
                ? $overtime->approveFinally($request->user(), $request->validated('review_note'))
                : $overtime->rejectFinally($request->user(), $request->validated('review_note'));
        });

        return to_route('admin.overtime.index', ['status' => LeaveStatus::Pending->value])->with('status', $approve
            ? __('Overtime for :name approved.', ['name' => $overtime->employee->name])
            : __('Overtime for :name rejected.', ['name' => $overtime->employee->name]));
    }
}
