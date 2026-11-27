<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\PayItemType;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PayItem;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * Works out one employee's pay for one month, in sen.
 *
 * Gross = basic (pro-rated for a partial month) + allowances + approved overtime − unpaid leave.
 * Net = gross − employee EPF, SOCSO, EIS, PCB and other deductions. Overtime is not subject to EPF.
 */
class PayrollCalculator
{
    /**
     * Payslip attributes, or null when the employee did not work in this month.
     *
     * @return array<string, mixed>|null
     */
    public function calculate(Employee $employee, Carbon $period): null|array
    {
        $monthStart = $period->copy()->startOfMonth();
        $monthEnd = $period->copy()->endOfMonth()->startOfDay();

        $from = $employee->join_date->max($monthStart);
        $to = ($employee->end_date ?? $monthEnd)->min($monthEnd);

        if ($from->gt($to)) {
            return null;
        }

        $workingDays = max(1, LeaveRequest::workingDays($monthStart, $monthEnd));
        $daysWorked = LeaveRequest::workingDays($from, $to);
        $salary = Money::toCents($employee->basic_salary);

        $prorate = fn (int $cents): int => $daysWorked >= $workingDays ? $cents : (int) round($cents * $daysWorked / $workingDays);

        $basicPay = $prorate($salary);
        $items = $employee->payItems;

        $allowanceLines = $items->toBase()->where('type', PayItemType::Allowance)
            ->map(fn (PayItem $item) => ['name' => $item->name, 'type' => 'allowance', 'amount' => $prorate(Money::toCents($item->amount))]);
        $allowances = $allowanceLines->sum('amount');

        $unpaidDays = $this->unpaidLeaveDays($employee, $from, $to);
        $unpaidDeduction = min($basicPay, (int) round($salary * $unpaidDays / $workingDays));

        $overtime = $this->overtime($employee, $salary, $monthEnd);

        $gross = $basicPay + $allowances + $overtime['pay'] - $unpaidDeduction;
        $epfWage = $gross - $overtime['pay'];

        $epfEmployee = $this->epf($epfWage, config('payroll.epf.employee_rate'));
        $epfEmployer = $this->epf($epfWage, $epfWage <= config('payroll.epf.threshold') * 100
            ? config('payroll.epf.employer_rate')
            : config('payroll.epf.employer_rate_above_threshold'));

        $socsoBase = min($gross, config('payroll.socso.wage_ceiling') * 100);
        $eisBase = min($gross, config('payroll.eis.wage_ceiling') * 100);
        $socsoEmployee = (int) round($socsoBase * config('payroll.socso.employee_rate') / 100);
        $socsoEmployer = (int) round($socsoBase * config('payroll.socso.employer_rate') / 100);
        $eisEmployee = (int) round($eisBase * config('payroll.eis.employee_rate') / 100);
        $eisEmployer = (int) round($eisBase * config('payroll.eis.employer_rate') / 100);

        // PCB and other deductions never take net pay below zero.
        $available = max(0, $gross - $epfEmployee - $socsoEmployee - $eisEmployee);
        $pcb = min(Money::toCents($employee->monthly_pcb), $available);

        $deductionLines = $items->toBase()->where('type', PayItemType::Deduction)
            ->map(fn (PayItem $item) => ['name' => $item->name, 'type' => 'deduction', 'amount' => Money::toCents($item->amount)]);
        $otherDeductions = min($deductionLines->sum('amount'), $available - $pcb);

        $totalDeductions = $epfEmployee + $socsoEmployee + $eisEmployee + $pcb + $otherDeductions;

        return [
            'employee_no' => $employee->employee_no,
            'employee_name' => $employee->name,
            'job_title' => $employee->job_title,
            'department_name' => $employee->department?->name,
            'bank_name' => $employee->bank_name,
            'bank_account_no' => $employee->bank_account_no,
            'working_days' => $workingDays,
            'days_worked' => $daysWorked,
            'unpaid_leave_days' => $unpaidDays,
            'basic_salary' => Money::fromCents($salary),
            'basic_pay' => Money::fromCents($basicPay),
            'allowances' => Money::fromCents($allowances),
            'overtime_hours' => number_format($overtime['hours'], 2, '.', ''),
            'overtime_pay' => Money::fromCents($overtime['pay']),
            'unpaid_leave_deduction' => Money::fromCents($unpaidDeduction),
            'gross_pay' => Money::fromCents($gross),
            'epf_employee' => Money::fromCents($epfEmployee),
            'socso_employee' => Money::fromCents($socsoEmployee),
            'eis_employee' => Money::fromCents($eisEmployee),
            'pcb' => Money::fromCents($pcb),
            'other_deductions' => Money::fromCents($otherDeductions),
            'total_deductions' => Money::fromCents($totalDeductions),
            'net_pay' => Money::fromCents($gross - $totalDeductions),
            'epf_employer' => Money::fromCents($epfEmployer),
            'socso_employer' => Money::fromCents($socsoEmployer),
            'eis_employer' => Money::fromCents($eisEmployer),
            'items' => $allowanceLines->merge($deductionLines)
                ->map(fn (array $line) => [...$line, 'amount' => Money::fromCents($line['amount'])])
                ->values()
                ->all(),
            // Not a payslip column: PayrollService links these overtime requests to the payslip.
            'overtime_ids' => $overtime['ids'],
        ];
    }

    /**
     * Approved overtime up to the end of the month that no payslip has paid yet.
     * Hourly rate = monthly basic / days per month / hours per day, times the day type's rate.
     *
     * @return array{hours: float, pay: int, ids: array<int, int>}
     */
    public function overtime(Employee $employee, int $salary, Carbon $monthEnd): array
    {
        $requests = $employee->overtimeRequests()->payableUntil($monthEnd)->get();
        $hoursInMonth = config('hr.overtime.days_per_month') * config('hr.overtime.hours_per_day');

        return [
            'hours' => (float) $requests->sum(fn (OvertimeRequest $overtime) => (float) $overtime->hours),
            'pay' => (int) $requests->sum(fn (OvertimeRequest $overtime) => round($salary * (float) $overtime->hours * $overtime->day_type->rate() / $hoursInMonth)),
            'ids' => $requests->modelKeys(),
        ];
    }

    /**
     * Working days of approved unpaid leave between two dates.
     */
    public function unpaidLeaveDays(Employee $employee, Carbon $from, Carbon $to): int
    {
        return $employee->leaveRequests()
            ->with('leaveType')
            ->where('status', LeaveStatus::Approved)
            ->whereHas('leaveType', fn ($query) => $query->where('is_paid', false))
            ->overlapping($from, $to)
            ->get()
            ->sum(fn (LeaveRequest $leave) => LeaveRequest::workingDays($leave->start_date->max($from), $leave->end_date->min($to)));
    }

    protected function epf(int $wage, float $rate): int
    {
        $amount = $wage * $rate / 100;

        return config('payroll.epf.round_up_to_ringgit')
            ? (int) (ceil($amount / 100) * 100)
            : (int) round($amount);
    }
}
