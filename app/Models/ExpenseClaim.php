<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\ExpenseClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee asking to be paid back for something they bought for work.
 * Status, review and posting fields are set by the app only.
 */
#[Fillable(['account_id', 'date', 'description', 'amount'])]
class ExpenseClaim extends Model
{
    /** @use HasFactory<ExpenseClaimFactory> */
    use Auditable, HasFactory;

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
}
