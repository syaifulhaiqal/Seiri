<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_see_the_self_service_guide(): void
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'web')->get(route('help'))
            ->assertOk()
            ->assertSee('Clocking in and out')
            ->assertSee('Applying for leave')
            ->assertDontSee('Running payroll')
            ->assertDontSee('Approving for your team');
    }

    public function test_admins_see_only_the_modules_their_role_can_use(): void
    {
        $hr = User::factory()->admin()->for(Role::factory()->with(Permission::EmployeesManage, Permission::AttendanceView), 'adminRole')->create();

        $this->actingAs($hr, 'admin')->get(route('admin.help'))
            ->assertOk()
            ->assertSee('Setting up employees')
            ->assertSee('Attendance')
            ->assertDontSee('Running payroll')
            ->assertDontSee('Audit trail');

        $this->actingAs(User::factory()->superAdmin()->create(), 'admin')->get(route('admin.help'))
            ->assertOk()
            ->assertSee('Running payroll')
            ->assertSee('Purchasing and stock')
            ->assertSee('Users and roles');
    }
}
