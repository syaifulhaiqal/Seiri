<?php

namespace Tests\Feature\Admin;

use App\Enums\ClaimStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\PayrollRun;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_only_reaches_the_modules_their_role_allows(): void
    {
        $hr = $this->adminWith(Permission::EmployeesManage, Permission::LeaveReview);

        $this->actingAs($hr, 'admin')->get(route('admin.employees.index'))->assertOk();
        $this->actingAs($hr, 'admin')->get(route('admin.employees.create'))->assertOk();
        $this->actingAs($hr, 'admin')->get(route('admin.leave_requests.index'))->assertOk();

        foreach (['admin.payroll.index', 'admin.invoices.index', 'admin.claims.index', 'admin.reports.balance_sheet', 'admin.audit.index', 'admin.users.index', 'admin.roles.index', 'admin.attendance.index'] as $route) {
            $this->actingAs($hr, 'admin')->get(route($route))->assertForbidden();
        }
    }

    public function test_the_menu_lists_only_permitted_modules(): void
    {
        $hr = $this->adminWith(Permission::EmployeesView);

        $this->actingAs($hr, 'admin')->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.employees.index'))
            ->assertDontSee(route('admin.payroll.index'))
            ->assertDontSee(route('admin.audit.index'))
            ->assertDontSee(route('admin.users.index'));
    }

    public function test_a_view_only_admin_cannot_change_records(): void
    {
        $viewer = $this->adminWith(Permission::EmployeesView);
        $employee = Employee::factory()->create();

        $this->actingAs($viewer, 'admin')->get(route('admin.employees.show', $employee))->assertOk()->assertDontSee(route('admin.employees.edit', $employee));
        $this->actingAs($viewer, 'admin')->get(route('admin.employees.edit', $employee))->assertForbidden();
        $this->actingAs($viewer, 'admin')->delete(route('admin.employees.destroy', $employee))->assertForbidden();

        $this->assertModelExists($employee);
    }

    public function test_preparing_payroll_does_not_include_approving_it(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
        $officer = $this->adminWith(Permission::PayrollManage);
        Employee::factory()->create(['join_date' => '2025-01-01', 'basic_salary' => '3000.00']);

        $this->actingAs($officer, 'admin')->get(route('admin.payroll.index'))->assertOk();
        $this->actingAs($officer, 'admin')->post(route('admin.payroll.store'), ['period' => today()->format('Y-m')])->assertRedirect();

        $run = PayrollRun::query()->sole();
        $this->actingAs($officer, 'admin')->get(route('admin.payroll.show', $run))->assertOk()->assertDontSee('Approve payroll');
        $this->actingAs($officer, 'admin')->post(route('admin.payroll.approve', $run))->assertForbidden();

        $this->assertTrue($run->refresh()->isDraft());
    }

    public function test_paying_back_claims_does_not_include_approving_them(): void
    {
        $cashier = $this->adminWith(Permission::ClaimsReimburse);
        $claim = ExpenseClaim::factory()->create();

        $this->actingAs($cashier, 'admin')->get(route('admin.claims.show', $claim))->assertOk()->assertDontSee('Decision');
        $this->actingAs($cashier, 'admin')->put(route('admin.claims.review', $claim), ['decision' => 'approve'])->assertForbidden();

        $this->assertSame(ClaimStatus::Pending, $claim->refresh()->status);
    }

    public function test_pay_settings_are_ignored_from_admins_without_payroll_rights(): void
    {
        $hr = $this->adminWith(Permission::EmployeesManage);
        $employee = Employee::factory()->create(['basic_salary' => '3000.00']);

        $this->actingAs($hr, 'admin')->get(route('admin.employees.edit', $employee))->assertOk()->assertDontSee('name="basic_salary"', false);

        $this->actingAs($hr, 'admin')->put(route('admin.employees.update', $employee), [
            ...$employee->only(['name', 'email', 'job_title', 'employee_no']),
            'employment_type' => $employee->employment_type->value,
            'status' => $employee->status->value,
            'join_date' => $employee->join_date->toDateString(),
            'basic_salary' => '99000',
        ])->assertSessionHasNoErrors();

        $this->assertSame('3000.00', $employee->refresh()->basic_salary);
    }

    public function test_an_admin_without_a_role_can_sign_in_but_use_nothing(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))->assertOk()->assertSee('No modules have been given to you yet.');
        $this->actingAs($admin, 'admin')->get(route('admin.employees.index'))->assertForbidden();
    }

    public function test_only_the_super_admin_manages_roles(): void
    {
        $ceo = User::factory()->superAdmin()->create();
        $userManager = $this->adminWith(Permission::UsersManage);

        $this->actingAs($userManager, 'admin')->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($userManager, 'admin')->post(route('admin.roles.store'), ['name' => 'Sneaky', 'permissions' => ['audit.view']])->assertForbidden();

        $this->actingAs($ceo, 'admin')->post(route('admin.roles.store'), [
            'name' => 'Receptionist',
            'permissions' => ['attendance.view', 'employees.view'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::query()->where('name', 'Receptionist')->sole();
        $this->assertTrue($role->grants(Permission::AttendanceView));
        $this->assertFalse($role->grants(Permission::LeaveReview));
    }

    public function test_a_role_needs_permissions_and_cannot_be_deleted_while_in_use(): void
    {
        $ceo = User::factory()->superAdmin()->create();

        $this->actingAs($ceo, 'admin')->post(route('admin.roles.store'), ['name' => 'Empty', 'permissions' => []])
            ->assertSessionHasErrors(['permissions' => 'Tick at least one permission.']);
        $this->actingAs($ceo, 'admin')->post(route('admin.roles.store'), ['name' => 'Bad', 'permissions' => ['everything']])
            ->assertSessionHasErrors('permissions.0');

        $role = Role::factory()->with(Permission::AuditView)->create();
        User::factory()->admin()->for($role, 'adminRole')->create();

        $this->actingAs($ceo, 'admin')->delete(route('admin.roles.destroy', $role))->assertSessionHasErrors('delete');
        $this->assertModelExists($role);
    }

    public function test_the_super_admin_gives_an_admin_a_role(): void
    {
        $ceo = User::factory()->superAdmin()->create();
        $user = User::factory()->create();
        $finance = Role::factory()->with(Permission::ReportsView)->create();
        $payload = ['name' => $user->name, 'email' => $user->email, 'role' => 'admin', 'is_active' => '1'];

        $this->actingAs($ceo, 'admin')->put(route('admin.users.update', $user), $payload)
            ->assertSessionHasErrors(['admin_role_id' => 'Choose which role this admin has. It decides the modules they can use.']);

        $this->actingAs($ceo, 'admin')->put(route('admin.users.update', $user), [...$payload, 'admin_role_id' => $finance->id])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->can('reports.view'));
        $this->assertFalse($user->can('audit.view'));

        // Turning them back into a user drops the admin role.
        $this->actingAs($ceo, 'admin')->put(route('admin.users.update', $user), [...$payload, 'role' => 'user'])->assertSessionHasNoErrors();
        $this->assertNull($user->refresh()->admin_role_id);
    }

    public function test_a_user_manager_cannot_touch_admins_or_hand_out_roles(): void
    {
        $manager = $this->adminWith(Permission::UsersManage);
        $otherAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $this->actingAs($manager, 'admin')->get(route('admin.users.edit', $otherAdmin))->assertForbidden();
        $this->actingAs($manager, 'admin')->delete(route('admin.users.destroy', $otherAdmin))->assertForbidden();

        $this->actingAs($manager, 'admin')->put(route('admin.users.update', $user), [
            'name' => 'Renamed', 'email' => $user->email, 'role' => 'super_admin', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Renamed', $user->name);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertModelExists($otherAdmin);
    }

    protected function adminWith(Permission ...$permissions): User
    {
        return User::factory()->admin()->for(Role::factory()->with(...$permissions), 'adminRole')->create();
    }
}
