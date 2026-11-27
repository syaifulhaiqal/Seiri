<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\LeaveStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * `user_id` (the linked login account) is not fillable; set it explicitly (see Admin\EmployeeController).
 */
#[Fillable([
    'employee_no', 'department_id', 'name', 'email', 'phone_number', 'job_title',
    'employment_type', 'status', 'join_date', 'end_date', 'date_of_birth', 'address',
    'basic_salary', 'monthly_pcb', 'bank_name', 'bank_account_no', 'can_work_remotely',
    'ic_no', 'tax_no',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'status' => EmployeeStatus::class,
            'join_date' => 'date',
            'end_date' => 'date',
            'date_of_birth' => 'date',
            'basic_salary' => 'decimal:2',
            'monthly_pcb' => 'decimal:2',
            'can_work_remotely' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $employee): void {
            $employee->employee_no ??= static::nextEmployeeNo();
        });
    }

    /**
     * The next free number, e.g. EMP-0007.
     */
    public static function nextEmployeeNo(): string
    {
        $prefix = config('hr.employee_no_prefix');
        $next = (static::query()->max('id') ?? 0) + 1;

        do {
            $number = $prefix . str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (static::query()->where('employee_no', $number)->exists());

        return $number;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<OvertimeRequest, $this>
     */
    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    /**
     * @return HasMany<Movement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    /**
     * @return HasMany<Training, $this>
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    /**
     * The employee's approval chains (who approves their leave, claims and overtime).
     *
     * @return HasMany<Approver, $this>
     */
    public function approvers(): HasMany
    {
        return $this->hasMany(Approver::class)->orderBy('type')->orderBy('level');
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @return HasMany<PayItem, $this>
     */
    public function payItems(): HasMany
    {
        return $this->hasMany(PayItem::class)->orderBy('type')->orderBy('name');
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * @return HasMany<ExpenseClaim, $this>
     */
    public function expenseClaims(): HasMany
    {
        return $this->hasMany(ExpenseClaim::class);
    }

    public function auditLabel(): string
    {
        return "{$this->name} ({$this->employee_no})";
    }

    public function isActive(): bool
    {
        return $this->status === EmployeeStatus::Active;
    }

    /**
     * Search by name, email, employee number or job title.
     */
    #[Scope]
    protected function search(Builder $query, null|string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('employee_no', 'like', "%{$term}%")
            ->orWhere('job_title', 'like', "%{$term}%"));
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', EmployeeStatus::Active);
    }

    /**
     * Days of this leave type already taken or waiting for approval in the given year.
     *
     * @return array{approved: int, pending: int}
     */
    public function leaveUsage(LeaveType $type, int $year): array
    {
        $totals = $this->leaveRequests()
            ->where('leave_type_id', $type->id)
            ->whereYear('start_date', $year)
            ->whereIn('status', LeaveStatus::counted())
            ->selectRaw('status, sum(days) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'approved' => (int) ($totals[LeaveStatus::Approved->value] ?? 0),
            'pending' => (int) ($totals[LeaveStatus::Pending->value] ?? 0),
        ];
    }

    /**
     * One row per leave type: entitlement, approved and pending days, and what is left.
     * `available` is null for leave types without a yearly limit.
     *
     * @return Collection<int, array{type: LeaveType, entitled: int, approved: int, pending: int, available: int|null}>
     */
    public function leaveBalances(int $year): Collection
    {
        return LeaveType::query()->orderBy('name')->get()->map(function (LeaveType $type) use ($year) {
            $usage = $this->leaveUsage($type, $year);

            return [
                'type' => $type,
                'entitled' => $type->days_per_year,
                'approved' => $usage['approved'],
                'pending' => $usage['pending'],
                'available' => $type->isUnlimited()
                    ? null
                    : max(0, $type->days_per_year - $usage['approved'] - $usage['pending']),
            ];
        });
    }

    public function isOnLeave(Carbon $date): bool
    {
        return $this->leaveRequests()
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }
}
