<?php

namespace App\Models;

use App\Enums\ApprovalType;
use App\Enums\LeaveStatus;
use App\Enums\OvertimeDayType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasApprovals;
use App\Notifications\OvertimeReviewed;
use App\Support\Notify;
use Database\Factories\OvertimeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Overtime an employee worked, paid in the next payroll once approved. Uses the same statuses as leave.
 * Hours and the day type (working day, rest day, public holiday) are worked out when it is saved.
 */
#[Fillable(['date', 'start_time', 'end_time', 'reason'])]
class OvertimeRequest extends Model
{
    /** @use HasFactory<OvertimeRequestFactory> */
    use Auditable, HasApprovals, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'decimal:2',
            'day_type' => OvertimeDayType::class,
            'status' => LeaveStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $overtime): void {
            $overtime->hours = self::hoursBetween($overtime->start_time, $overtime->end_time);
            $overtime->day_type = OvertimeDayType::for($overtime->date);
        });
    }

    /**
     * Hours between two "HH:MM" times. An end before the start means the shift ran past midnight.
     */
    public static function hoursBetween(string $start, string $end): float
    {
        $from = Carbon::parse($start);
        $to = Carbon::parse($end);

        if ($to->lte($from)) {
            $to->addDay();
        }

        return round($from->diffInMinutes($to) / 60, 2);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo<Payslip, $this>
     */
    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function auditLabel(): string
    {
        return __('Overtime for :name on :date', [
            'name' => $this->employee?->name ?? '#' . $this->employee_id,
            'date' => $this->date?->format('j M Y'),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === LeaveStatus::Pending;
    }

    /**
     * "6:00 PM – 9:30 PM".
     */
    public function timeRange(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A') . ' – ' . Carbon::parse($this->end_time)->format('g:i A');
    }

    public function approvalType(): ApprovalType
    {
        return ApprovalType::Overtime;
    }

    public function approvalSummary(): string
    {
        return __(':hours h overtime on :date (:type, ×:rate)', [
            'hours' => (float) $this->hours,
            'date' => $this->date->translatedFormat('D, j M Y'),
            'type' => $this->day_type->label(),
            'rate' => $this->day_type->rate(),
        ]);
    }

    public function approveFinally(User $by, null|string $note): void
    {
        $this->decide(LeaveStatus::Approved, $by, $note);
    }

    public function rejectFinally(User $by, string $note): void
    {
        $this->decide(LeaveStatus::Rejected, $by, $note);
    }

    protected function decide(LeaveStatus $status, User $by, null|string $note): void
    {
        $this->status = $status;
        $this->review_note = $note;
        $this->reviewer()->associate($by);
        $this->reviewed_at = now();
        $this->save();

        Notify::employee($this->employee, new OvertimeReviewed($this));
    }

    /**
     * Pending or approved: the claims that block another claim for the same day.
     */
    #[Scope]
    protected function counted(Builder $query): void
    {
        $query->whereIn('status', LeaveStatus::counted());
    }

    /**
     * Approved and not yet in a payslip, up to the end of the given month.
     */
    #[Scope]
    protected function payableUntil(Builder $query, Carbon $monthEnd): void
    {
        $query->where('status', LeaveStatus::Approved)
            ->whereNull('payslip_id')
            ->whereDate('date', '<=', $monthEnd);
    }
}
