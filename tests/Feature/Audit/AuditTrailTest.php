<?php

namespace Tests\Feature\Audit;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_record_who_changed_which_fields(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Nurul Admin']);
        $employee = Employee::factory()->create(['job_title' => 'Clerk']);

        $this->actingAs($admin, 'admin')->put(route('admin.employees.update', $employee), [
            ...$employee->only(['name', 'email', 'employment_type', 'status']),
            'employment_type' => $employee->employment_type->value,
            'status' => $employee->status->value,
            'join_date' => $employee->join_date->toDateString(),
            'job_title' => 'Senior clerk',
            'employee_no' => $employee->employee_no,
        ])->assertSessionHasNoErrors();

        $log = AuditLog::query()->where('event', AuditEvent::Updated)->where('subject_type', 'employee')->sole();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Nurul Admin', $log->user_name);
        $this->assertSame(['old' => 'Clerk', 'new' => 'Senior clerk'], $log->changes()['job_title']);
    }

    public function test_hidden_fields_are_recorded_without_their_values(): void
    {
        $user = User::factory()->create();

        $user->update(['password' => 'a-new-secret-123']);

        $log = AuditLog::query()->where('event', AuditEvent::Updated)->where('subject_type', 'user')->sole();
        $this->assertArrayHasKey('password', $log->changes());
        $this->assertStringNotContainsString($user->refresh()->password, json_encode($log->changes()));
        $this->assertStringNotContainsString('a-new-secret-123', json_encode($log->changes()));
    }

    public function test_sign_ins_and_failed_attempts_are_recorded(): void
    {
        $user = User::factory()->create(['email' => 'aina@example.com', 'password' => 'password123']);

        $this->post(route('login'), ['email' => 'aina@example.com', 'password' => 'wrong-password']);
        $this->post(route('login'), ['email' => 'aina@example.com', 'password' => 'password123']);

        $failed = AuditLog::query()->where('event', AuditEvent::LoginFailed)->sole();
        $this->assertNull($failed->user_id);
        $this->assertSame('aina@example.com', $failed->changes()['email']['new']);
        $this->assertStringNotContainsString('wrong-password', json_encode($failed->getAttributes()));

        $this->assertSame($user->id, AuditLog::query()->where('event', AuditEvent::Login)->sole()->user_id);
    }

    public function test_entries_cannot_be_changed_or_deleted(): void
    {
        $log = AuditLog::record(AuditEvent::Created, description: 'Original');

        try {
            $log->update(['description' => 'Tampered']);
            $this->fail('An audit entry was updated.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_admin_can_filter_and_export_the_trail(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create(['name' => 'Farah Aziz']);
        AuditLog::record(AuditEvent::Approved, $employee, description: 'Leave approved');

        $this->actingAs($admin, 'admin')->get(route('admin.audit.index', ['event' => 'approved']))
            ->assertOk()
            ->assertSee('Leave approved')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1);

        $csv = $this->actingAs($admin, 'admin')->get(route('admin.audit.export', ['event' => 'approved']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Leave approved', $csv);
        $this->assertStringContainsString('Farah Aziz', $csv);
        $this->assertSame(2, substr_count(trim($csv), "\n") + 1, 'One header row and one entry.');
    }

    public function test_regular_users_cannot_read_the_trail(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'admin')->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($user, 'admin')->get(route('admin.audit.export'))->assertForbidden();
    }
}
