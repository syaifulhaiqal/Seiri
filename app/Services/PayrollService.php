<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\EmployeeStatus;
use App\Enums\PayrollStatus;
use App\Models\Account;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Generating, approving and paying monthly payroll runs.
 */
class PayrollService
{
    public function __construct(protected PayrollCalculator $calculator, protected Ledger $ledger) {}

    /**
     * (Re)calculate every payslip in a draft run from the employees' current pay settings.
     */
    public function generate(PayrollRun $run): void
    {
        if (! $run->isDraft()) {
            throw new InvalidArgumentException('Only draft payroll runs can be recalculated.');
        }

        DB::transaction(function () use ($run): void {
            $run->payslips()->delete();

            foreach ($this->payableEmployees($run->period) as $employee) {
                $attributes = $this->calculator->calculate($employee, $run->period);

                if ($attributes === null) {
                    continue;
                }

                $payslip = $run->payslips()->make($attributes);
                $payslip->employee_id = $employee->id;
                $payslip->save();
            }

            $this->refreshTotals($run);
        });
    }

    /**
     * Lock the run and post the payroll cost and what is owed (net pay, EPF, SOCSO, EIS, PCB).
     */
    public function approve(PayrollRun $run, User $approver): void
    {
        if (! $run->isDraft()) {
            throw new InvalidArgumentException('This payroll run has already been approved.');
        }

        $payslips = $run->payslips()->get();

        if ($payslips->isEmpty()) {
            throw new InvalidArgumentException('There are no payslips to approve.');
        }

        $sum = fn (string $column): int => $payslips->sum(fn ($payslip) => Money::toCents($payslip->{$column}));

        DB::transaction(function () use ($run, $approver, $sum): void {
            $entry = $this->ledger->post($run->period->copy()->endOfMonth(), __('Payroll :period', ['period' => $run->periodLabel()]), [
                ['account' => 'salary_expense', 'debit' => $sum('gross_pay')],
                ['account' => 'epf_expense', 'debit' => $sum('epf_employer')],
                ['account' => 'socso_expense', 'debit' => $sum('socso_employer')],
                ['account' => 'eis_expense', 'debit' => $sum('eis_employer')],
                ['account' => 'salaries_payable', 'credit' => $sum('net_pay')],
                ['account' => 'epf_payable', 'credit' => $sum('epf_employee') + $sum('epf_employer')],
                ['account' => 'socso_payable', 'credit' => $sum('socso_employee') + $sum('socso_employer')],
                ['account' => 'eis_payable', 'credit' => $sum('eis_employee') + $sum('eis_employer')],
                ['account' => 'pcb_payable', 'credit' => $sum('pcb')],
                ['account' => 'other_payroll_payable', 'credit' => $sum('other_deductions')],
            ], $run);

            $run->status = PayrollStatus::Approved;
            $run->approved_by = $approver->id;
            $run->approved_at = now();
            $run->journal_entry_id = $entry->id;
            $run->save();
            $run->audit(AuditEvent::Approved);
        });
    }

    /**
     * Record that net salaries left the bank.
     */
    public function markPaid(PayrollRun $run, Account $from, Carbon|string $date): void
    {
        if ($run->status !== PayrollStatus::Approved) {
            throw new InvalidArgumentException('Only approved payroll runs can be marked as paid.');
        }

        DB::transaction(function () use ($run, $from, $date): void {
            $net = Money::toCents($run->total_net);

            $entry = $this->ledger->post($date, __('Salaries paid :period', ['period' => $run->periodLabel()]), [
                ['account' => 'salaries_payable', 'debit' => $net],
                ['account' => $from, 'credit' => $net],
            ], $run);

            $run->status = PayrollStatus::Paid;
            $run->paid_at = Carbon::parse($date);
            $run->payment_entry_id = $entry->id;
            $run->save();
            $run->audit(AuditEvent::Paid);
        });
    }

    /**
     * Employees with a salary who were employed at some point in the month.
     *
     * @return Collection<int, Employee>
     */
    public function payableEmployees(Carbon $period): Collection
    {
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();

        return Employee::query()
            ->with(['department', 'payItems'])
            ->where('basic_salary', '>', 0)
            ->whereDate('join_date', '<=', $end)
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->where('status', EmployeeStatus::Active)->whereNull('end_date'))
                ->orWhereDate('end_date', '>=', $start))
            ->orderBy('name')
            ->get();
    }

    protected function refreshTotals(PayrollRun $run): void
    {
        $payslips = $run->payslips()->get();
        $sum = fn (string ...$columns): string => Money::fromCents($payslips->sum(fn ($payslip) => collect($columns)->sum(fn ($column) => Money::toCents($payslip->{$column}))));

        $run->employee_count = $payslips->count();
        $run->total_gross = $sum('gross_pay');
        $run->total_deductions = $sum('total_deductions');
        $run->total_net = $sum('net_pay');
        $run->total_employer_contributions = $sum('epf_employer', 'socso_employer', 'eis_employer');
        $run->save();
    }
}
