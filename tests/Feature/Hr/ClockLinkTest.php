<?php

namespace Tests\Feature\Hr;

use App\Enums\ClockMethod;
use App\Enums\Permission;
use App\Models\ClockLink;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClockLinkTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-03-04 08:50:00');
        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_hr_creates_a_link_and_any_employee_clocks_in_and_out_with_it(): void
    {
        $hr = User::factory()->admin()->for(Role::factory()->with(Permission::EmployeesManage), 'adminRole')->create();

        $this->actingAs($hr, 'admin')->post(route('admin.attendance.links.store'), ['name' => 'Work from home', 'require_location' => '0'])->assertRedirect();
        $link = ClockLink::query()->sole();
        $this->actingAs($hr, 'admin')->get(route('admin.attendance.links.show', $link))->assertOk()->assertSee($link->url())->assertSee('<svg', false);

        // Not allowed to work remotely, yet the shared link works for every employee.
        $this->assertFalse($this->employee->fresh()->can_work_remotely);
        $other = Employee::factory()->create(['user_id' => User::factory()]);

        $this->actingAs($this->user, 'web')->get($link->url())->assertOk()->assertSee('Clock in now');
        $this->actingAs($this->user, 'web')->post(route('clock_link.store', $link->token), [
            'latitude' => '3.1390030',
            'longitude' => '101.6868550',
        ])->assertRedirect(route('attendance.index'))->assertSessionHasNoErrors();
        $this->actingAs($other->user, 'web')->post(route('clock_link.store', $link->token))->assertSessionHasNoErrors();

        Carbon::setTestNow('2026-03-04 18:00:00');
        $this->actingAs($this->user, 'web')->post(route('clock_link.store', $link->token))->assertSessionHasNoErrors();

        $attendance = $this->employee->attendances()->sole();
        $this->assertSame(ClockMethod::Link, $attendance->clock_in_method);
        $this->assertSame(ClockMethod::Link, $attendance->clock_out_method);
        $this->assertSame('Work from home', $attendance->work_location);
        $this->assertSame(9.2, $attendance->workedHours());
        $this->assertSame(1, $other->attendances()->count());
    }

    public function test_guests_sign_in_first_and_come_back_to_the_link(): void
    {
        $link = ClockLink::factory()->create();

        $this->get($link->url())->assertRedirect(route('login'));
        $this->assertSame($link->url(), session('url.intended'));
    }

    public function test_renewed_expired_and_turned_off_links_stop_working(): void
    {
        $hr = User::factory()->superAdmin()->create();
        $link = ClockLink::factory()->create();
        $oldUrl = $link->url();

        $this->actingAs($hr, 'admin')->put(route('admin.attendance.links.renew', $link))->assertRedirect();
        $this->actingAs($this->user, 'web')->get($oldUrl)->assertRedirect(route('attendance.index'))->assertSessionHasErrors('delete');
        $this->actingAs($this->user, 'web')->get($link->fresh()->url())->assertOk();

        $link->forceFill(['expires_on' => '2026-03-03'])->save();
        $this->actingAs($this->user, 'web')->post(route('clock_link.store', $link->fresh()->token))->assertSessionHasErrors('delete');

        $link->forceFill(['expires_on' => null])->save();
        $token = $link->fresh()->token;
        $this->actingAs($hr, 'admin')->delete(route('admin.attendance.links.destroy', $link))->assertRedirect(route('admin.attendance.links.index'));
        $this->actingAs($this->user, 'web')->post(route('clock_link.store', $token))->assertSessionHasErrors('delete');

        $this->assertSame(0, $this->employee->attendances()->count());
    }

    public function test_a_link_can_require_location_and_former_staff_are_refused(): void
    {
        $link = ClockLink::factory()->create(['require_location' => true]);

        $this->actingAs($this->user, 'web')->post(route('clock_link.store', $link->token))->assertSessionHasErrors('delete');
        $this->assertSame(0, $this->employee->attendances()->count());

        $this->employee->update(['status' => 'resigned', 'end_date' => '2026-02-28']);
        $this->actingAs($this->user->fresh(), 'web')->post(route('clock_link.store', $link->token), [
            'latitude' => '3.1390030',
            'longitude' => '101.6868550',
        ])->assertSessionHasErrors('delete');
        $this->assertSame(0, $this->employee->attendances()->count());
    }

    public function test_only_admins_who_manage_employees_can_manage_links(): void
    {
        $auditor = User::factory()->admin()->for(Role::factory()->with(Permission::AttendanceView), 'adminRole')->create();

        $this->actingAs($auditor, 'admin')->get(route('admin.attendance.links.index'))->assertForbidden();
        $this->actingAs($auditor, 'admin')->post(route('admin.attendance.links.store'), ['name' => 'WFH'])->assertForbidden();
    }
}
