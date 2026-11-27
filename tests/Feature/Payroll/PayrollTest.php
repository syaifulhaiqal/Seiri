<?php

namespace Tests\Feature\Payroll;

use App\Enums\PayItemType;
use App\Enums\PayrollStatus;
use App\Models\Account;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\FinancialReports;
use App\Services\PayrollCalculator;
use App\Support\Money;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Carbon $march;

    protected function setUp(): void
    {
        parent::setUp();

        // March 2026 has 22 working days.
        Carbon::setTestNow('2026-03-31 17:00:00');
        $this->march = Carbon::parse('2026-03-01');
        $this->seed(ChartOfAccountsSeeder::class);
        $this->admin = User::factory()->superAdmin()->create();
    }

    public function test_statutory_deductions_and_pay_items_are_worked_out(): void
    {
        $employee = Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '4021.00', 'monthly_pcb' => '100.00']);
        $employee->payItems()->create(['name' => 'Transport', 'type' => PayItemType::Allowance, 'amount' => '300.00']);
        $employee->payItems()->create(['name' => 'Staff loan', 'type' => PayItemType::Deduction, 'amount' => '50.00']);

        $pay = app(PayrollCalculator::class)->calculate($employee->load('payItems'), $this->march);

        $this->assertSame('4321.00', $pay['gross_pay']);
        $this->assertSame('476.00', $pay['epf_employee'], 'EPF is 11%, rounded up to the next ringgit.');
        $this->assertSame('562.00', $pay['epf_employer'], 'Employer EPF is 13% up to RM5,000.');
        $this->assertSame('21.61', $pay['socso_employee']);
        $this->assertSame('75.62', $pay['socso_employer']);
        $this->assertSame('8.64', $pay['eis_employee']);
        $this->assertSame('100.00', $pay['pcb']);
        $this->assertSame('50.00', $pay['other_deductions']);
        $this->assertSame('3664.75', $pay['net_pay']);
    }

    public function test_pay_is_pro_rated_for_a_mid_month_joiner_and_unpaid_leave_is_deducted(): void
    {
        $joiner = Employee::factory()->create(['join_date' => '2026-03-16', 'basic_salary' => '2200.00']);
        $onLeave = Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '2200.00']);
        $unpaid = LeaveType::factory()->create(['name' => 'Unpaid leave', 'is_paid' => false]);
        LeaveRequest::factory()->approved()->for($onLeave)->for($unpaid)->create(['start_date' => '2026-03-09', 'end_date' => '2026-03-10', 'days' => 2]);

        $calculator = app(PayrollCalculator::class);
        $joinerPay = $calculator->calculate($joiner, $this->march);
        $leavePay = $calculator->calculate($onLeave, $this->march);

        $this->assertSame(12, $joinerPay['days_worked']);
        $this->assertSame('1200.00', $joinerPay['basic_pay']);
        $this->assertSame(2, $leavePay['unpaid_leave_days']);
        $this->assertSame('200.00', $leavePay['unpaid_leave_deduction']);
        $this->assertSame('2000.00', $leavePay['gross_pay']);
    }

    public function test_a_run_is_prepared_approved_and_paid_through_the_ledger(): void
    {
        $paid = Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '3000.00']);
        Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '0']);

        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.store'), ['period' => '2026-03'])->assertRedirect();

        $run = PayrollRun::query()->sole();
        $this->assertTrue($run->isDraft());
        $this->assertSame(1, $run->employee_count);
        $this->assertSame($paid->id, $run->payslips()->sole()->employee_id);

        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.approve', $run))->assertRedirect();

        $run->refresh();
        $this->assertSame(PayrollStatus::Approved, $run->status);
        $this->assertSame($this->admin->id, $run->approved_by);
        $this->assertSame(300000, $this->balance('salary_expense'));
        $this->assertSame((int) round($run->total_net * 100), $this->balance('salaries_payable'));

        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.pay', $run), [
            'account_id' => Account::system('bank')->id, 'date' => '2026-03-31',
        ])->assertRedirect();

        $this->assertSame(PayrollStatus::Paid, $run->refresh()->status);
        $this->assertSame(0, $this->balance('salaries_payable'));
        $this->assertSame(-(int) round($run->total_net * 100), $this->balance('bank'));

        $trial = app(FinancialReports::class)->trialBalance(today());
        $this->assertSame($trial['debit'], $trial['credit']);
    }

    public function test_a_month_can_only_be_run_once_and_approved_runs_are_locked(): void
    {
        Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '3000.00']);
        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.store'), ['period' => '2026-03']);
        $run = PayrollRun::query()->sole();

        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.store'), ['period' => '2026-03'])->assertSessionHasErrors('period');

        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.approve', $run));
        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.recalculate', $run))->assertSessionHasErrors('delete');
        $this->actingAs($this->admin, 'admin')->delete(route('admin.payroll.destroy', $run))->assertSessionHasErrors('delete');

        $this->assertModelExists($run);
        $this->assertSame(1, PayrollRun::query()->count());
    }

    public function test_employees_see_only_their_own_approved_payslips(): void
    {
        $user = User::factory()->create();
        $mine = Employee::factory()->create(['user_id' => $user->id, 'join_date' => '2025-01-01', 'basic_salary' => '3000.00']);
        Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '3500.00']);

        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.store'), ['period' => '2026-03']);
        $run = PayrollRun::query()->sole();
        $myPayslip = $run->payslips()->where('employee_id', $mine->id)->sole();
        $otherPayslip = $run->payslips()->where('employee_id', '!=', $mine->id)->sole();

        $this->actingAs($user, 'web')->get(route('payslips.show', $myPayslip))->assertNotFound();
        $this->actingAs($user, 'web')->get(route('payslips.index'))->assertOk()->assertDontSee('March 2026');

        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.approve', $run));

        $this->actingAs($user, 'web')->get(route('payslips.index'))->assertOk()->assertSee('March 2026');
        $this->actingAs($user, 'web')->get(route('payslips.show', $myPayslip))
            ->assertOk()
            ->assertSee('Net pay')
            ->assertSee('Employer contributions')
            ->assertSee(Money::format($myPayslip->employerContributions()));
        $this->actingAs($user, 'web')->get(route('payslips.show', $otherPayslip))->assertNotFound();
    }

    public function test_employees_with_payslips_cannot_be_deleted(): void
    {
        $employee = Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '3000.00']);
        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.store'), ['period' => '2026-03']);

        $this->actingAs($this->admin, 'admin')->delete(route('admin.employees.destroy', $employee))->assertSessionHasErrors('delete');

        $this->assertModelExists($employee);
    }

    public function test_an_earlier_month_is_prepared_correctly_at_the_end_of_a_long_month(): void
    {
        Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '3000.00']);

        // On 31 March, "2026-02" must not overflow into March.
        $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.store'), ['period' => '2026-02'])->assertSessionHasNoErrors();

        $this->assertSame('2026-02-01', PayrollRun::query()->sole()->period->toDateString());
    }

    protected function balance(string $key): int
    {
        return app(FinancialReports::class)->balances()[Account::system($key)->id];
    }
}
