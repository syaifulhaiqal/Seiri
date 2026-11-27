<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\ClaimStatus;
use App\Models\Account;
use App\Models\ExpenseClaim;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Approving, rejecting and paying back expense claims.
 */
class ExpenseClaimPosting
{
    public function __construct(protected Ledger $ledger) {}

    /**
     * Approving books the expense and what the company now owes the employee.
     */
    public function approve(ExpenseClaim $claim, User $reviewer, null|string $note = null): void
    {
        $this->ensureStatus($claim, ClaimStatus::Pending);

        DB::transaction(function () use ($claim, $reviewer, $note): void {
            $claim->loadMissing(['employee', 'account']);
            $amount = Money::toCents($claim->amount);

            $entry = $this->ledger->post($claim->date, __('Expense claim :number · :name', ['number' => $claim->number, 'name' => $claim->employee->name]), [
                ['account' => $claim->account, 'debit' => $amount, 'description' => $claim->description],
                ['account' => 'claims_payable', 'credit' => $amount],
            ], $claim, $claim->number);

            $this->review($claim, ClaimStatus::Approved, $reviewer, $note);
            $claim->journal_entry_id = $entry->id;
            $claim->save();
            $claim->audit(AuditEvent::Approved, $note);
        });
    }

    public function reject(ExpenseClaim $claim, User $reviewer, string $note): void
    {
        $this->ensureStatus($claim, ClaimStatus::Pending);

        $this->review($claim, ClaimStatus::Rejected, $reviewer, $note);
        $claim->save();
        $claim->audit(AuditEvent::Rejected, $note);
    }

    /**
     * Paying the employee back clears what the company owes them.
     */
    public function reimburse(ExpenseClaim $claim, Account $from, Carbon|string $date): void
    {
        $this->ensureStatus($claim, ClaimStatus::Approved);

        DB::transaction(function () use ($claim, $from, $date): void {
            $amount = Money::toCents($claim->amount);

            $entry = $this->ledger->post($date, __('Claim reimbursed :number · :name', ['number' => $claim->number, 'name' => $claim->employee->name]), [
                ['account' => 'claims_payable', 'debit' => $amount],
                ['account' => $from, 'credit' => $amount],
            ], $claim, $claim->number);

            $claim->status = ClaimStatus::Reimbursed;
            $claim->reimbursed_at = Carbon::parse($date);
            $claim->reimbursement_entry_id = $entry->id;
            $claim->save();
            $claim->audit(AuditEvent::Paid);
        });
    }

    protected function review(ExpenseClaim $claim, ClaimStatus $status, User $reviewer, null|string $note): void
    {
        $claim->status = $status;
        $claim->reviewed_by = $reviewer->id;
        $claim->reviewed_at = now();
        $claim->review_note = $note;
    }

    protected function ensureStatus(ExpenseClaim $claim, ClaimStatus $expected): void
    {
        if ($claim->status !== $expected) {
            throw new InvalidArgumentException("Claim {$claim->number} is {$claim->status->label()}.");
        }
    }
}
