<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\Approver;
use App\Models\User;
use App\Notifications\ApprovalRequested;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Runs a request through the employee's approval chain, one approver at a time.
 *
 * Without a chain the request goes straight to the admins who review it (the HR or finance pages), as before.
 * Admins with the review permission can still decide any pending request directly; the remaining steps are then
 * marked as not needed.
 *
 * The request model uses App\Models\Concerns\HasApprovals.
 */
class ApprovalFlow
{
    /**
     * Create the steps for a new request and tell the first approver. False when the employee has no chain.
     */
    public function start(Model $request): bool
    {
        $approvers = Approver::query()
            ->where('employee_id', $request->employee_id)
            ->where('type', $request->approvalType())
            ->orderBy('level')
            ->get();

        if ($approvers->isEmpty()) {
            return false;
        }

        foreach ($approvers as $approver) {
            $request->approvals()->create([
                'level' => $approver->level,
                'approver_id' => $approver->approver_id,
                'status' => ApprovalStatus::Pending,
            ]);
        }

        $this->notifyCurrent($request);

        return true;
    }

    /**
     * Record one approver's decision. Rejecting ends the chain; approving passes it to the next approver,
     * or approves the request when this was the last one.
     *
     * @throws ValidationException
     */
    public function decide(Approval $approval, User $approver, bool $approve, null|string $note): void
    {
        $request = $approval->approvable;

        if ($approval->approver_id !== $approver->id || ! $approval->is($request->currentApproval())) {
            throw ValidationException::withMessages(['review' => __('This request is no longer waiting for you.')]);
        }

        DB::transaction(function () use ($approval, $approver, $approve, $note, $request): void {
            $approval->status = $approve ? ApprovalStatus::Approved : ApprovalStatus::Rejected;
            $approval->note = $note;
            $approval->decided_at = now();
            $approval->save();

            if (! $approve) {
                $this->close($request);
                $request->rejectFinally($approver, (string) $note);

                return;
            }

            $request->unsetRelation('approvals');

            if ($request->currentApproval()) {
                $this->notifyCurrent($request);

                return;
            }

            $request->approveFinally($approver, $note);
        });
    }

    /**
     * Mark the steps nobody needs to decide any more (HR decided directly, rejected, or cancelled).
     */
    public function close(Model $request): void
    {
        $request->approvals()->pending()->update(['status' => ApprovalStatus::Skipped, 'decided_at' => now()]);
        $request->unsetRelation('approvals');
    }

    protected function notifyCurrent(Model $request): void
    {
        $request->unsetRelation('approvals');
        $current = $request->currentApproval();

        $current?->approver->notify(new ApprovalRequested($current));
    }
}
