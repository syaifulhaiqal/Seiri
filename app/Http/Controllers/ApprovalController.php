<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Http\Requests\ApprovalDecisionRequest;
use App\Models\Approval;
use App\Models\ExpenseClaim;
use App\Services\ApprovalFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * For staff who approve other employees' requests (team leads, managers): what is waiting for them.
 */
class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $mine = Approval::query()
            ->where('approver_id', $request->user()->id)
            ->with(['approvable.employee.department', 'approvable.approvals.approver']);

        return view('approvals.index', [
            'waiting' => (clone $mine)->pending()->oldest()->get()
                ->filter(fn (Approval $approval) => $approval->is($approval->approvable->currentApproval()))
                ->values(),
            'decided' => (clone $mine)->whereIn('status', [ApprovalStatus::Approved, ApprovalStatus::Rejected])->latest('decided_at')->limit(10)->get(),
        ]);
    }

    public function decide(ApprovalDecisionRequest $request, Approval $approval, ApprovalFlow $flow): RedirectResponse
    {
        $flow->decide($approval, $request->user(), $request->approves(), $request->validated('note'));

        return to_route('approvals.index')->with('status', $request->approves()
            ? __('Approved. :name will be told once every approver has decided.', ['name' => $approval->approvable->employee->name])
            : __('Rejected. :name has been told.', ['name' => $approval->approvable->employee->name]));
    }

    /**
     * The receipt of a claim waiting for this approver.
     */
    public function receipt(Request $request, Approval $approval): StreamedResponse
    {
        $claim = $approval->approvable;

        abort_unless($approval->approver_id === $request->user()->id && $claim instanceof ExpenseClaim, 403);

        return $claim->receiptResponse();
    }
}
