<?php

namespace App\Models;

use App\Enums\ApprovalType;
use App\Enums\ClaimStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasApprovals;
use App\Notifications\ClaimUpdated;
use App\Services\ExpenseClaimPosting;
use App\Support\Money;
use App\Support\Notify;
use Database\Factories\ExpenseClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * An employee asking to be paid back for something they bought for work.
 * Status, review and posting fields are set by the app only.
 */
#[Fillable(['account_id', 'date', 'description', 'amount'])]
class ExpenseClaim extends Model
{
    /** @use HasFactory<ExpenseClaimFactory> */
    use Auditable, HasApprovals, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'status' => ClaimStatus::class,
            'reviewed_at' => 'datetime',
            'reimbursed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The expense account (category) the claim is charged to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function auditLabel(): string
    {
        return $this->number;
    }

    public function isPending(): bool
    {
        return $this->status === ClaimStatus::Pending;
    }

    public function approvalType(): ApprovalType
    {
        return ApprovalType::Claim;
    }

    public function approvalSummary(): string
    {
        return __(':number · :amount for :description', [
            'number' => $this->number,
            'amount' => Money::format($this->amount),
            'description' => $this->description,
        ]);
    }

    /**
     * Approve it (by finance, or the last approver in the chain): books the expense and tells the employee.
     */
    public function approveFinally(User $by, null|string $note): void
    {
        app(ExpenseClaimPosting::class)->approve($this, $by, $note);

        Notify::employee($this->employee, new ClaimUpdated($this));
    }

    public function rejectFinally(User $by, string $note): void
    {
        app(ExpenseClaimPosting::class)->reject($this, $by, $note);

        Notify::employee($this->employee, new ClaimUpdated($this));
    }

    /**
     * The uploaded receipt, shown in the browser (it lives on the private disk, never under public/).
     */
    public function receiptResponse(): StreamedResponse
    {
        abort_unless($this->receipt_path && Storage::disk('local')->exists($this->receipt_path), 404);

        return Storage::disk('local')->response($this->receipt_path, $this->receipt_name);
    }
}
