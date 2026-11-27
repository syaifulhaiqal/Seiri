<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Enums\Permission;
use App\Http\Requests\OvertimeApplyRequest;
use App\Models\OvertimeRequest;
use App\Notifications\OvertimeRequested;
use App\Services\ApprovalFlow;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Self-service overtime claims. Approved overtime is paid in the next payroll.
 */
class OvertimeController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('overtime.index', [
            'employee' => $employee,
            'requests' => $employee
                ? $employee->overtimeRequests()->with(['payslip.payrollRun', 'approvals.approver'])->latest('date')->paginate(10)
                : null,
        ]);
    }

    public function store(OvertimeApplyRequest $request): RedirectResponse
    {
        $overtime = new OvertimeRequest($request->validated());
        $overtime->employee()->associate($request->user()->employee);
        $overtime->status = LeaveStatus::Pending;
        $overtime->save();

        if (! app(ApprovalFlow::class)->start($overtime)) {
            Notify::admins(Permission::OvertimeReview, new OvertimeRequested($overtime));
        }

        return to_route('overtime.index')->with('status', __('Overtime claim for :hours h sent. You will see the decision here.', ['hours' => (float) $overtime->hours]));
    }

    public function cancel(Request $request, OvertimeRequest $overtime): RedirectResponse
    {
        abort_unless($overtime->employee_id === $request->user()->employee?->id, 403);

        if (! $overtime->isPending()) {
            return back()->withErrors(['delete' => __('Only pending claims can be cancelled.')]);
        }

        $overtime->status = LeaveStatus::Cancelled;
        $overtime->save();
        app(ApprovalFlow::class)->close($overtime);

        return to_route('overtime.index')->with('status', __('Overtime claim cancelled.'));
    }
}
