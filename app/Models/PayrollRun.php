<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One month's payroll. Draft runs can be recalculated; approved runs are posted to the ledger and locked.
 */
#[Fillable(['period'])]
class PayrollRun extends Model
{
    use Auditable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => 'date',
            'status' => PayrollStatus::class,
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_employer_contributions' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class)->orderBy('employee_name');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function paymentEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'payment_entry_id');
    }

    public function isDraft(): bool
    {
        return $this->status === PayrollStatus::Draft;
    }

    /**
     * "November 2026".
     */
    public function periodLabel(): string
    {
        return $this->period->format('F Y');
    }

    public function auditLabel(): string
    {
        return __('Payroll :period', ['period' => $this->periodLabel()]);
    }

    /**
     * Total cost to the company: gross pay plus employer contributions.
     */
    public function totalCost(): float
    {
        return (float) $this->total_gross + (float) $this->total_employer_contributions;
    }
}
