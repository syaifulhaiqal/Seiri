<?php

namespace App\Services;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payslip;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

/**
 * The yearly statement of remuneration (in the layout of Borang EA) from an employee's approved payslips.
 * Draft payroll is left out. Amounts are strings with two decimals.
 */
class EaStatement
{
    /**
     * @return array{
     *     year: int,
     *     employee: Employee,
     *     months: int,
     *     salary: string,
     *     overtime: string,
     *     allowances: string,
     *     unpaid_leave: string,
     *     gross: string,
     *     pcb: string,
     *     epf_employee: string,
     *     socso_employee: string,
     *     eis_employee: string,
     *     epf_employer: string,
     *     socso_employer: string,
     *     eis_employer: string,
     *     payslips: Collection<int, Payslip>,
     * }|null
     */
    public function for(Employee $employee, int $year): null|array
    {
        $payslips = $this->payslips($employee->payslips(), $year)->with('payrollRun')->get()
            ->sortBy(fn (Payslip $payslip) => $payslip->payrollRun->period)
            ->values();

        if ($payslips->isEmpty()) {
            return null;
        }

        $sum = fn (string $column): string => Money::fromCents($payslips->sum(fn (Payslip $payslip) => Money::toCents($payslip->{$column})));

        return [
            'year' => $year,
            'employee' => $employee,
            'months' => $payslips->count(),
            'salary' => $sum('basic_pay'),
            'overtime' => $sum('overtime_pay'),
            'allowances' => $sum('allowances'),
            'unpaid_leave' => $sum('unpaid_leave_deduction'),
            'gross' => $sum('gross_pay'),
            'pcb' => $sum('pcb'),
            'epf_employee' => $sum('epf_employee'),
            'socso_employee' => $sum('socso_employee'),
            'eis_employee' => $sum('eis_employee'),
            'epf_employer' => $sum('epf_employer'),
            'socso_employer' => $sum('socso_employer'),
            'eis_employer' => $sum('eis_employer'),
            'payslips' => $payslips,
        ];
    }

    /**
     * Years in which the employee has approved payslips, newest first.
     *
     * @return array<int, int>
     */
    public function years(Employee $employee): array
    {
        return $employee->payslips()
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->where('payroll_runs.status', '!=', PayrollStatus::Draft)
            ->pluck('payroll_runs.period')
            ->map(fn ($period) => (int) substr((string) $period, 0, 4))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * Totals for every employee paid in the year, for the admin list.
     *
     * @return SupportCollection<int, array{employee: Employee, months: int, gross: string, pcb: string, epf_employee: string}>
     */
    public function summary(int $year): SupportCollection
    {
        return $this->payslips(Payslip::query(), $year)
            ->with('employee')
            ->get()
            ->groupBy('employee_id')
            ->map(function ($payslips) {
                $sum = fn (string $column): string => Money::fromCents($payslips->sum(fn (Payslip $payslip) => Money::toCents($payslip->{$column})));

                return [
                    'employee' => $payslips->first()->employee,
                    'months' => $payslips->count(),
                    'gross' => $sum('gross_pay'),
                    'pcb' => $sum('pcb'),
                    'epf_employee' => $sum('epf_employee'),
                ];
            })
            ->sortBy(fn (array $row) => $row['employee']->name)
            ->values();
    }

    /**
     * @template TQuery of Builder<Payslip>|HasMany<Payslip, Employee>
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    protected function payslips(Builder|HasMany $query, int $year): Builder|HasMany
    {
        return $query->whereHas('payrollRun', fn (Builder $query) => $query
            ->where('status', '!=', PayrollStatus::Draft)
            ->whereYear('period', $year));
    }
}
