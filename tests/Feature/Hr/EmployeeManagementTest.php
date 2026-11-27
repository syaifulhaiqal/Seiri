<?php

namespace Tests\Feature\Hr;

use App\Enums\EmployeeStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_users_cannot_reach_hr_admin_pages(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->get(route('admin.employees.index'))->assertRedirect(route('admin.login'));
        $this->actingAs($user, 'admin')->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($user, 'admin')->get(route('admin.departments.index'))->assertForbidden();
        $this->actingAs($user, 'admin')->get(route('admin.leave_requests.index'))->assertForbidden();
        $this->actingAs($user, 'admin')->get(route('admin.attendance.index'))->assertForbidden();
        $this->actingAs($user, 'admin')->delete(route('admin.employees.destroy', $employee))->assertForbidden();

        $this->assertModelExists($employee);
    }

    public function test_admin_can_list_and_filter_employees(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $finance = Department::factory()->create(['name' => 'Finance']);
        Employee::factory()->for($finance)->create(['name' => 'Aisyah Rahman']);
        Employee::factory()->resigned()->create(['name' => 'Daniel Lim']);

        $this->actingAs($admin, 'admin')->get(route('admin.employees.index'))
            ->assertOk()
            ->assertSee('Aisyah Rahman')
            ->assertSee('Daniel Lim');

        $this->actingAs($admin, 'admin')->get(route('admin.employees.index', ['department' => $finance->id]))
            ->assertSee('Aisyah Rahman')
            ->assertDontSee('Daniel Lim');

        $this->actingAs($admin, 'admin')->get(route('admin.employees.index', ['status' => 'resigned']))
            ->assertSee('Daniel Lim')
            ->assertDontSee('Aisyah Rahman');
    }

    public function test_admin_can_add_an_employee_with_a_generated_number_and_linked_login(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $login = User::factory()->create();
        $department = Department::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.employees.store'), [
            'name' => 'Nur Iman',
            'email' => 'iman@example.com',
            'department_id' => $department->id,
            'job_title' => 'Accountant',
            'employment_type' => 'permanent',
            'status' => 'active',
            'join_date' => '2025-01-06',
            'user_id' => $login->id,
        ]);

        $employee = Employee::query()->where('email', 'iman@example.com')->firstOrFail();
        $response->assertRedirect(route('admin.employees.show', $employee));

        $this->assertSame('EMP-0001', $employee->employee_no);
        $this->assertTrue($employee->user->is($login));
        $this->actingAs($admin, 'admin')->get(route('admin.employees.show', $employee))->assertOk()->assertSee('Nur Iman');
    }

    public function test_an_admin_login_or_an_already_linked_login_cannot_be_linked(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $taken = Employee::factory()->create(['user_id' => User::factory()->create()->id]);

        $data = [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'job_title' => 'Clerk',
            'employment_type' => 'contract',
            'status' => 'active',
            'join_date' => '2025-01-06',
        ];

        $this->actingAs($admin, 'admin')->post(route('admin.employees.store'), [...$data, 'user_id' => $admin->id])
            ->assertSessionHasErrors('user_id');
        $this->actingAs($admin, 'admin')->post(route('admin.employees.store'), [...$data, 'user_id' => $taken->user_id])
            ->assertSessionHasErrors('user_id');
    }

    public function test_leaving_an_employee_requires_a_last_working_day(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($admin, 'admin')->put(route('admin.employees.update', $employee), [
            'employee_no' => $employee->employee_no,
            'name' => $employee->name,
            'email' => $employee->email,
            'job_title' => $employee->job_title,
            'employment_type' => 'permanent',
            'status' => 'resigned',
            'join_date' => $employee->join_date->toDateString(),
        ])->assertSessionHasErrors('end_date');

        $this->actingAs($admin, 'admin')->put(route('admin.employees.update', $employee), [
            'employee_no' => $employee->employee_no,
            'name' => $employee->name,
            'email' => $employee->email,
            'job_title' => $employee->job_title,
            'employment_type' => 'permanent',
            'status' => 'resigned',
            'join_date' => $employee->join_date->toDateString(),
            'end_date' => today()->toDateString(),
        ])->assertRedirect(route('admin.employees.show', $employee));

        $this->assertSame(EmployeeStatus::Resigned, $employee->fresh()->status);
    }

    public function test_admin_can_delete_an_employee(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($admin, 'admin')->delete(route('admin.employees.destroy', $employee))
            ->assertRedirect(route('admin.employees.index'));

        $this->assertModelMissing($employee);
    }

    public function test_admin_can_manage_departments(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')->post(route('admin.departments.store'), ['name' => 'Finance', 'code' => 'fin'])
            ->assertRedirect(route('admin.departments.index'));

        $department = Department::query()->where('code', 'FIN')->firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('admin.departments.store'), ['name' => 'Other', 'code' => 'FIN'])
            ->assertSessionHasErrors('code');

        $employee = Employee::factory()->for($department)->create();

        $this->actingAs($admin, 'admin')->delete(route('admin.departments.destroy', $department))
            ->assertRedirect(route('admin.departments.index'));

        $this->assertModelMissing($department);
        $this->assertNull($employee->fresh()->department_id);
    }
}
