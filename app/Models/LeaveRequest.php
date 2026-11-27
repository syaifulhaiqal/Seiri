<?php

namespace App\Models;

use App\Enums\ApprovalType;
use App\Enums\LeaveStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasApprovals;
use App\Notifications\LeaveReviewed;
use App\Support\DateRange;
use App\Support\HolidayCalendar;
use App\Support\Notify;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * `status` and the review fields are not fillable: only the approve / reject / cancel actions change them.
 */
#[Fillable(['leave_type_id', 'start_date', 'end_date', 'days', 'reason'])]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use Auditable, HasApprovals, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'integer',
            'status' => LeaveStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Working days between two dates, both included. Weekends and public holidays are not counted.
     */
    public static function workingDays(Carbon $start, Carbon $end): int
    {
        return app(HolidayCalendar::class)->workingDays($start, $end);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
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
        return __(':type for :name', [
            'type' => $this->leaveType?->name ?? __('Leave'),
            'name' => $this->employee?->name ?? '#' . $this->employee_id,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === LeaveStatus::Pending;
    }

    public function approvalType(): ApprovalType
    {
        return ApprovalType::Leave;
    }

    public function approvalSummary(): string
    {
        return trans_choice(':type · :dates (:days working day)|:type · :dates (:days working days)', $this->days, [
            'type' => $this->leaveType->name,
            'dates' => DateRange::format($this->start_date, $this->end_date),
            'days' => $this->days,
        ]);
    }

    /**
     * Approve it (by HR, or the last approver in the chain) and tell the employee.
     */
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

        Notify::employee($this->employee, new LeaveReviewed($this));
    }

    /**
     * Requests whose dates overlap the given range.
     */
    #[Scope]
    protected function overlapping(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start);
    }

    /**
     * Pending and approved requests: the ones that hold days.
     */
    #[Scope]
    protected function counted(Builder $query): void
    {
        $query->whereIn('status', LeaveStatus::counted());
    }
}
