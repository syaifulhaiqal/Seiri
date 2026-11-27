<?php

namespace App\Models\Concerns;

use App\Enums\ApprovalType;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A request that goes through the employee's approval chain (leave, expense claims, overtime).
 * See App\Services\ApprovalFlow.
 *
 * @property Employee $employee
 */
trait HasApprovals
{
    abstract public function approvalType(): ApprovalType;

    /**
     * One line for the approver, e.g. "Annual leave · 6–10 Mar 2026 (3 days)".
     */
    abstract public function approvalSummary(): string;

    abstract public function isPending(): bool;

    /**
     * The last approver said yes: approve the request for real (and tell the employee).
     */
    abstract public function approveFinally(User $by, null|string $note): void;

    abstract public function rejectFinally(User $by, string $note): void;

    /**
     * @return MorphMany<Approval, $this>
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable')->orderBy('level');
    }

    /**
     * The step waiting for a decision now, if the request is still pending.
     */
    public function currentApproval(): null|Approval
    {
        if (! $this->isPending()) {
            return null;
        }

        return $this->relationLoaded('approvals')
            ? $this->approvals->first(fn (Approval $approval) => $approval->isPending())
            : $this->approvals()->pending()->first();
    }
}
