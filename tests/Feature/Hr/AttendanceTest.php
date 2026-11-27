<?php

namespace Tests\Feature\Hr;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_clock_in_and_out_once_a_day(): void
    {
        Carbon::setTestNow('2026-03-04 09:20:00');
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('attendance.clock_in'))->assertRedirect(route('attendance.index'));
        $this->actingAs($user)->post(route('attendance.clock_in'))->assertSessionHasErrors('delete');

        Carbon::setTestNow('2026-03-04 18:05:00');
        $this->actingAs($user)->post(route('attendance.clock_out'))->assertRedirect(route('attendance.index'));

        $attendance = $employee->attendances()->sole();
        $this->assertTrue($attendance->isLate());
        $this->assertSame(8.8, $attendance->workedHours());

        $this->actingAs($user)->get(route('attendance.index'))->assertOk()->assertSee('Done for today');
    }

    public function test_cannot_clock_out_before_clocking_in(): void
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('attendance.clock_out'))->assertSessionHasErrors('delete');
    }

    public function test_former_employees_cannot_clock_in(): void
    {
        $user = User::factory()->create();
        Employee::factory()->resigned()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('attendance.clock_in'))->assertForbidden();
    }

    public function test_admin_sees_present_late_on_leave_and_absent_for_a_day(): void
    {
        Carbon::setTestNow('2026-03-04 15:00:00');
        $admin = User::factory()->admin()->create();
        $onTime = Employee::factory()->create(['name' => 'On Time']);
        $late = Employee::factory()->create(['name' => 'Running Late']);
        $away = Employee::factory()->create(['name' => 'Away Today']);
        Employee::factory()->create(['name' => 'Not In']);

        Attendance::factory()->for($onTime)->create(['clock_in_at' => '2026-03-04 08:45:00']);
        Attendance::factory()->for($late)->create(['clock_in_at' => '2026-03-04 09:30:00']);
        LeaveRequest::factory()->for($away)->approved()->create(['start_date' => '2026-03-04', 'end_date' => '2026-03-05', 'days' => 2]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.index'))->assertOk();
        $counts = $response->viewData('counts');

        $this->assertSame(['present' => 1, 'late' => 1, 'on_leave' => 1, 'absent' => 1], $counts->all());

        $this->actingAs($admin, 'admin')->get(route('admin.attendance.index', ['status' => 'absent']))
            ->assertSee('Not In')
            ->assertDontSee('Running Late');
    }
}
