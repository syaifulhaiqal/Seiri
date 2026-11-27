<?php

namespace Tests\Feature\Hr;

use App\Enums\ClockMethod;
use App\Enums\Permission;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClockInTest extends TestCase
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

    public function test_scanning_the_office_qr_code_clocks_in_then_out(): void
    {
        $this->actingAs($this->user, 'web')->get($this->scanUrl())->assertOk()->assertSee('Clock in now');
        $this->actingAs($this->user, 'web')->post(route('attendance.qr'))->assertRedirect(route('attendance.index'))->assertSessionHasNoErrors();

        Carbon::setTestNow('2026-03-04 18:00:00');
        $this->actingAs($this->user, 'web')->get($this->scanUrl())->assertSee('Clock out now');
        $this->actingAs($this->user, 'web')->post(route('attendance.qr'))->assertSessionHasNoErrors();

        $attendance = $this->employee->attendances()->sole();
        $this->assertSame(ClockMethod::Qr, $attendance->clock_in_method);
        $this->assertSame(ClockMethod::Qr, $attendance->clock_out_method);
        $this->assertSame('127.0.0.1', $attendance->clock_in_ip);
        $this->assertSame(9.2, $attendance->workedHours());
    }

    public function test_an_old_or_unscanned_qr_code_is_refused(): void
    {
        $url = $this->scanUrl();
        Carbon::setTestNow('2026-03-04 09:00:00');

        $this->actingAs($this->user, 'web')->get($url)->assertRedirect(route('attendance.index'))->assertSessionHasErrors('delete');
        $this->actingAs($this->user, 'web')->post(route('attendance.qr'))->assertSessionHasErrors('delete');

        $this->assertSame(0, $this->employee->attendances()->count());
    }

    public function test_the_qr_code_can_be_limited_to_the_office_network(): void
    {
        config(['hr.office_ips' => ['203.0.113.10']]);

        $this->actingAs($this->user, 'web')->get($this->scanUrl());
        $this->actingAs($this->user, 'web')->post(route('attendance.qr'))->assertSessionHasErrors('delete');
        $this->assertSame(0, $this->employee->attendances()->count());

        $this->actingAs($this->user, 'web')->get($this->scanUrl());
        $this->actingAs($this->user, 'web')->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])->post(route('attendance.qr'))->assertSessionHasNoErrors();
        $this->assertSame(1, $this->employee->attendances()->count());
    }

    public function test_only_staff_allowed_to_work_remotely_can_use_the_link_and_hr_sees_where(): void
    {
        $this->actingAs($this->user, 'web')->post(route('attendance.clock_in'))->assertSessionHasErrors('delete');
        $this->assertSame(0, $this->employee->attendances()->count());

        $this->employee->update(['can_work_remotely' => true]);
        $this->actingAs($this->user->fresh(), 'web')->post(route('attendance.clock_in'), [
            'latitude' => '3.1390030',
            'longitude' => '101.6868550',
            'work_location' => 'Home',
        ])->assertSessionHasNoErrors();

        $attendance = $this->employee->attendances()->sole();
        $this->assertSame(ClockMethod::Remote, $attendance->clock_in_method);
        $this->assertSame('Home', $attendance->work_location);

        $hr = User::factory()->admin()->for(Role::factory()->with(Permission::AttendanceView), 'adminRole')->create();
        $this->actingAs($hr, 'admin')->get(route('admin.attendance.index', ['method' => 'remote']))
            ->assertOk()
            ->assertSee($this->employee->name)
            ->assertSee('Home')
            ->assertSee('https://www.google.com/maps?q=3.1390030,101.6868550', false);

        $this->actingAs($hr, 'admin')->get(route('admin.attendance.qr'))->assertOk()->assertSee('<svg', false);
        $this->actingAs($hr, 'admin')->getJson(route('admin.attendance.qr_code'))->assertOk()->assertJsonStructure(['svg']);
    }

    protected function scanUrl(): string
    {
        return URL::temporarySignedRoute('attendance.scan', now()->addMinutes(5));
    }
}
