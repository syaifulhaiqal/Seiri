<?php

namespace Tests\Feature\Hr;

use App\Enums\LeaveStatus;
use App\Enums\OvertimeDayType;
use App\Enums\Permission;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\PayrollRun;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OvertimeRequested;
use App\Notifications\OvertimeReviewed;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OvertimeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // 1 March 2026 is a Sunday.
        Carbon::setTestNow('2026-03-31 17:00:00');
        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id, 'join_date' => '2025-01-01', 'basic_salary' => '2600.00']);
    }

    public function test_an_employee_claims_overtime_and_hr_is_told(): void
    {
        Notification::fake();
        $hr = User::factory()->admin()->for(Role::factory()->with(Permission::OvertimeReview), 'adminRole')->create();

        $this->actingAs($this->user, 'web')->post(route('overtime.store'), [
            'date' => '2026-03-07',
            'start_time' => '22:00',
            'end_time' => '01:30',
            'reason' => 'Server migration',
        ])->assertRedirect(route('overtime.index'));

        $overtime = OvertimeRequest::query()->sole();
        $this->assertSame('3.50', $overtime->hours, 'A shift past midnight counts the hours after it.');
        $this->assertSame(OvertimeDayType::RestDay, $overtime->day_type);
        $this->assertSame(LeaveStatus::Pending, $overtime->status);
        Notification::assertSentTo($hr, OvertimeRequested::class);
        $this->actingAs($this->user, 'web')->get(route('overtime.index'))->assertOk()->assertSee('×2');
        $this->actingAs($hr, 'admin')->get(route('admin.overtime.show', $overtime))->assertOk()->assertSee('Server migration');

        $this->actingAs($this->user, 'web')->post(route('overtime.store'), [
            'date' => '2026-03-07', 'start_time' => '09:00', 'end_time' => '10:00', 'reason' => 'Again',
        ])->assertSessionHasErrorsIn('apply', 'date');

        $this->actingAs($this->user, 'web')->post(route('overtime.store'), [
            'date' => '2026-04-01', 'start_time' => '18:00', 'end_time' => '20:00', 'reason' => 'Not worked yet',
        ])->assertSessionHasErrorsIn('apply', 'date');

        $this->assertSame(1, OvertimeRequest::query()->count());
    }

    public function test_approved_overtime_is_paid_once_and_is_not_subject_to_epf(): void
    {
        Notification::fake();
        $this->seed(ChartOfAccountsSeeder::class);
        $admin = User::factory()->superAdmin()->create();

        // Hourly rate is 2,600 / 26 / 8 = RM12.50.
        $weekday = OvertimeRequest::factory()->for($this->employee)->create(['date' => '2026-03-10', 'start_time' => '18:00', 'end_time' => '20:00']);
        OvertimeRequest::factory()->approved()->for($this->employee)->create(['date' => '2026-03-07', 'start_time' => '09:00', 'end_time' => '13:00']);
        OvertimeRequest::factory()->for($this->employee)->create(['date' => '2026-03-11']);

        $this->actingAs($admin, 'admin')->put(route('admin.overtime.review', $weekday), ['decision' => 'approve'])->assertRedirect();

        $this->assertSame(LeaveStatus::Approved, $weekday->refresh()->status);
        Notification::assertSentTo($this->user, OvertimeReviewed::class);

        $this->actingAs($admin, 'admin')->post(route('admin.payroll.store'), ['period' => '2026-03']);
        $payslip = PayrollRun::query()->sole()->payslips()->sole();

        $this->assertSame('6.00', $payslip->overtime_hours);
        $this->assertSame('137.50', $payslip->overtime_pay, '2 h × 1.5 + 4 h × 2.0 on a rest day.');
        $this->assertSame('2737.50', $payslip->gross_pay);
        $this->assertSame('286.00', $payslip->epf_employee, 'EPF is on the basic salary only.');
        $this->assertSame(2, OvertimeRequest::query()->where('payslip_id', $payslip->id)->count(), 'The pending claim is not paid.');

        $this->actingAs($admin, 'admin')->get(route('admin.payroll.payslip', [$payslip->payrollRun, $payslip]))->assertOk()->assertSee('Overtime (6 h)');

        // Recalculating the draft keeps the same overtime; the next month does not pay it again.
        $this->actingAs($admin, 'admin')->post(route('admin.payroll.recalculate', $payslip->payrollRun));
        $this->assertSame('137.50', PayrollRun::query()->sole()->payslips()->sole()->overtime_pay);

        $this->actingAs($admin, 'admin')->post(route('admin.payroll.approve', $payslip->payrollRun));
        Carbon::setTestNow('2026-04-30 17:00:00');
        $this->actingAs($admin, 'admin')->post(route('admin.payroll.store'), ['period' => '2026-04']);

        $april = PayrollRun::query()->latest('id')->first()->payslips()->sole();
        $this->assertSame('0.00', $april->overtime_pay);
    }

    public function test_overtime_review_needs_the_permission(): void
    {
        $overtime = OvertimeRequest::factory()->for($this->employee)->create();
        $clerk = User::factory()->admin()->for(Role::factory()->with(Permission::LeaveReview), 'adminRole')->create();

        $this->actingAs($clerk, 'admin')->get(route('admin.overtime.index'))->assertForbidden();
        $this->actingAs($clerk, 'admin')->put(route('admin.overtime.review', $overtime), ['decision' => 'approve'])->assertForbidden();

        $this->assertSame(LeaveStatus::Pending, $overtime->refresh()->status);
    }

    public function test_an_employee_logs_a_movement_and_hr_sees_who_is_out(): void
    {
        $this->actingAs($this->user, 'web')->post(route('movements.store'), [
            'date' => '2026-03-31', 'time_out' => '14:00', 'destination' => 'Client office', 'purpose' => 'Contract signing',
        ])->assertRedirect(route('movements.index'));

        $movement = $this->employee->movements()->sole();
        $this->assertTrue($movement->isOut());
        $this->actingAs($this->user, 'web')->get(route('movements.index'))->assertOk()->assertSee('Contract signing');

        $hr = User::factory()->admin()->for(Role::factory()->with(Permission::AttendanceView), 'adminRole')->create();
        $this->actingAs($hr, 'admin')->get(route('admin.movements.index'))->assertOk()->assertSee('Client office')->assertSee('Still out');

        $other = Employee::factory()->create(['user_id' => User::factory()])->user;
        $this->actingAs($other, 'web')->put(route('movements.back', $movement))->assertForbidden();

        $this->actingAs($this->user, 'web')->put(route('movements.back', $movement))->assertRedirect();
        $this->assertSame('17:00', substr($movement->refresh()->time_back, 0, 5));
    }

    public function test_hr_records_training_that_the_employee_can_see(): void
    {
        $hr = User::factory()->admin()->for(Role::factory()->with(Permission::TrainingManage), 'adminRole')->create();

        $this->actingAs($hr, 'admin')->post(route('admin.trainings.store'), [
            'employee_id' => $this->employee->id,
            'title' => 'Occupational first aid',
            'start_date' => '2026-03-16',
            'end_date' => '2026-03-17',
            'hours' => '14',
            'status' => 'completed',
        ])->assertRedirect();

        $training = $this->employee->trainings()->sole();
        $this->assertSame('0.00', $training->cost);
        $this->actingAs($hr, 'admin')->get(route('admin.trainings.index'))->assertOk()->assertSee('Occupational first aid');
        $this->actingAs($hr, 'admin')->get(route('admin.trainings.edit', $training))->assertOk();
        $this->actingAs($hr, 'admin')->get(route('admin.trainings.create', ['employee' => $this->employee->id]))->assertOk();

        $this->actingAs($this->user, 'web')->get(route('trainings.index'))
            ->assertOk()
            ->assertSee('Occupational first aid')
            ->assertSee('14 training hours completed this year');

        $viewer = User::factory()->admin()->for(Role::factory()->with(Permission::EmployeesView), 'adminRole')->create();
        $this->actingAs($viewer, 'admin')->get(route('admin.trainings.index'))->assertForbidden();
    }
}
