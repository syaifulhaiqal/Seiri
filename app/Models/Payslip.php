<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One employee's pay for one payroll run. Written by App\Services\PayrollCalculator only.
 */
class Payslip extends Model
{
    protected $guarded = ['id', 'payroll_run_id', 'employee_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        $money = collect([
            'basic_salary', 'basic_pay', 'allowances', 'overtime_pay', 'unpaid_leave_deduction', 'gross_pay',
            'epf_employee', 'socso_employee', 'eis_employee', 'pcb', 'other_deductions',
            'total_deductions', 'net_pay', 'epf_employer', 'socso_employer', 'eis_employer',
        ])->mapWithKeys(fn (string $column) => [$column => 'decimal:2'])->all();

        return [
            ...$money,
            'items' => 'array',
            'working_days' => 'integer',
            'days_worked' => 'integer',
            'unpaid_leave_days' => 'integer',
            'overtime_hours' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<PayrollRun, $this>
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function employerContributions(): float
    {
        return (float) $this->epf_employer + (float) $this->socso_employer + (float) $this->eis_employer;
    }
}
