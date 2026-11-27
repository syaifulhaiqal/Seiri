<?php

namespace Tests\Feature;

use App\Enums\AuditEvent;
use App\Enums\Permission;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_send_browser_protection_headers(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_the_audit_trail_hides_pay_and_bank_details_from_admins_without_payroll_access(): void
    {
        $employee = Employee::factory()->create(['basic_salary' => 3000, 'bank_account_no' => '1234567890']);
        $employee->update(['basic_salary' => 4500, 'bank_account_no' => '9999888877']);
        $log = AuditLog::query()->where('event', AuditEvent::Updated)->where('subject_type', 'employee')->sole();

        $auditor = User::factory()->admin()->for(Role::factory()->with(Permission::AuditView), 'adminRole')->create();
        $this->actingAs($auditor, 'admin')->get(route('admin.audit.show', $log))
            ->assertOk()
            ->assertSee('basic_salary')
            ->assertDontSee('4500')
            ->assertDontSee('9999888877');
        $this->assertStringNotContainsString('9999888877', $this->actingAs($auditor, 'admin')->get(route('admin.audit.export'))->streamedContent());

        $payroll = User::factory()->admin()->for(Role::factory()->with(Permission::AuditView, Permission::PayrollView), 'adminRole')->create();
        $this->actingAs($payroll, 'admin')->get(route('admin.audit.show', $log))->assertSee('9999888877');
    }

    public function test_exported_cells_cannot_run_as_spreadsheet_formulas(): void
    {
        Employee::factory()->create(['name' => '=HYPERLINK("http://evil.test","Click")']);
        $admin = User::factory()->superAdmin()->create();

        $csv = $this->actingAs($admin, 'admin')->get(route('admin.audit.export'))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
        $this->assertStringNotContainsString(',"=HYPERLINK', $csv);
    }

    public function test_sign_up_is_rate_limited(): void
    {
        foreach (range(1, 6) as $attempt) {
            $this->post(route('register.store'), ['email' => 'not-an-email'])->assertSessionHasErrors();
        }

        $this->post(route('register.store'), ['email' => 'not-an-email'])->assertTooManyRequests();
    }
}
