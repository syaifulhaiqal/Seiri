<?php

namespace Tests\Feature\Hr;

use App\Enums\AttendanceStatus;
use App\Enums\Permission;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use App\Services\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HolidayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-10 10:00:00');
    }

    public function test_holidays_are_not_working_days_for_leave_payroll_or_attendance(): void
    {
        // Mon 31 Aug 2026 is Merdeka.
        Holiday::factory()->create(['date' => '2026-08-31', 'name' => 'National Day']);

        $this->assertSame(4, LeaveRequest::workingDays(Carbon::parse('2026-08-31'), Carbon::parse('2026-09-04')));
        $this->assertSame(AttendanceStatus::Holiday, AttendanceStatus::resolve(Carbon::parse('2026-08-31'), null, false));

        $employee = Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '3000.00']);
        $this->assertSame(20, app(PayrollCalculator::class)->calculate($employee, Carbon::parse('2026-08-01'))['working_days'], 'August 2026 has 21 weekdays, less Merdeka.');
    }

    public function test_leave_on_only_a_holiday_is_refused(): void
    {
        Holiday::factory()->create(['date' => '2026-08-31']);
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'web')->post(route('leave.store'), [
            'leave_type_id' => LeaveType::factory()->create()->id,
            'start_date' => '2026-08-31',
            'end_date' => '2026-08-31',
        ])->assertSessionHasErrorsIn('apply', ['end_date' => 'These dates fall on a weekend or public holiday. Choose at least one working day.']);
    }

    public function test_hr_manages_the_holiday_calendar(): void
    {
        $hr = User::factory()->admin()->for(Role::factory()->with(Permission::HolidaysManage), 'adminRole')->create();

        $this->actingAs($hr, 'admin')->post(route('admin.holidays.store'), ['date' => '2026-03-21', 'name' => 'Hari Raya Aidilfitri'])
            ->assertRedirect(route('admin.holidays.index', ['year' => 2026]));
        $this->actingAs($hr, 'admin')->post(route('admin.holidays.store'), ['date' => '2026-03-21', 'name' => 'Duplicate'])
            ->assertSessionHasErrors('date');

        $this->actingAs($hr, 'admin')->get(route('admin.holidays.index', ['year' => 2026]))->assertOk()->assertSee('Hari Raya Aidilfitri');

        $outsider = User::factory()->admin()->for(Role::factory()->with(Permission::LeaveReview), 'adminRole')->create();
        $this->actingAs($outsider, 'admin')->get(route('admin.holidays.index'))->assertForbidden();
    }
}
