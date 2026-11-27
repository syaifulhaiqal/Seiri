<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Models\Concerns\Auditable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
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
    use Auditable, HasFactory;

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
     * Weekdays between two dates, both included. Weekends are not counted.
     */
    public static function workingDays(Carbon $start, Carbon $end): int
    {
        if ($end->lt($start)) {
            return 0;
        }

        return collect(CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()))
            ->reject(fn (CarbonInterface $day) => $day->isWeekend())
            ->count();
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
