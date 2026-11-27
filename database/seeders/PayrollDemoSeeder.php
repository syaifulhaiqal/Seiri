<?php

namespace Database\Seeders;

use App\Enums\PayItemType;
use App\Models\Account;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Database\Seeder;

/**
 * Allowances, deductions, PCB, two paid payroll runs and this month's draft.
 */
class PayrollDemoSeeder extends Seeder
{
    public function __construct(protected PayrollService $payroll) {}

    public function run(): void
    {
        $admin = User::query()->where('email', config('admin.email'))->firstOrFail();

        Employee::query()->active()->get()->each(function (Employee $employee, int $index): void {
            $employee->monthly_pcb = match (true) {
                $employee->basic_salary >= 8000 => 520,
                $employee->basic_salary >= 6000 => 260,
                $employee->basic_salary >= 5000 => 120,
                $employee->basic_salary >= 4000 => 45,
                default => 0,
            };
            $employee->save();

            if ($index % 2 === 0) {
                $employee->payItems()->create(['name' => 'Transport allowance', 'type' => PayItemType::Allowance, 'amount' => 200]);
            }

            if ($index % 3 === 0) {
                $employee->payItems()->create(['name' => 'Phone allowance', 'type' => PayItemType::Allowance, 'amount' => 100]);
            }

            if ($index % 7 === 0) {
                $employee->payItems()->create(['name' => 'Staff loan repayment', 'type' => PayItemType::Deduction, 'amount' => 150]);
            }
        });

        $bank = Account::system('bank');

        foreach ([2, 1] as $monthsAgo) {
            $run = PayrollRun::query()->create(['period' => today()->subMonths($monthsAgo)->startOfMonth()]);
            $this->payroll->generate($run);
            $this->payroll->approve($run, $admin);
            $this->payroll->markPaid($run->refresh(), $bank, $run->period->copy()->endOfMonth()->min(today()));
        }

        $current = PayrollRun::query()->create(['period' => today()->startOfMonth()]);
        $this->payroll->generate($current);
    }
}
