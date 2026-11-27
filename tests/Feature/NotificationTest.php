<?php

namespace Tests\Feature;

use App\Enums\LeaveStatus;
use App\Enums\Permission;
use App\Models\Account;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AdminRoleChanged;
use App\Notifications\ClaimSubmitted;
use App\Notifications\ClaimUpdated;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveReviewed;
use App\Notifications\PayrollAwaitingApproval;
use App\Notifications\PayslipReady;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-03-31 10:00:00');

        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id, 'join_date' => '2025-01-01', 'basic_salary' => '3000.00']);
    }

    public function test_leave_requests_go_to_reviewers_and_decisions_go_back_to_the_employee(): void
    {
        Notification::fake();
        $reviewer = $this->adminWith(Permission::LeaveReview);
        $payrollOfficer = $this->adminWith(Permission::PayrollManage);
        $annual = LeaveType::factory()->create(['days_per_year' => 10]);

        $this->actingAs($this->user, 'web')->post(route('leave.store'), [
            'leave_type_id' => $annual->id,
            'start_date' => '2026-04-06',
            'end_date' => '2026-04-07',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($reviewer, LeaveRequested::class);
        Notification::assertNotSentTo($payrollOfficer, LeaveRequested::class);

        $leave = LeaveRequest::query()->sole();
        $this->actingAs($reviewer, 'admin')->put(route('admin.leave_requests.review', $leave), [
            'decision' => 'reject',
            'review_note' => 'Audit week',
        ])->assertRedirect();

        Notification::assertSentTo($this->user, LeaveReviewed::class, fn (LeaveReviewed $notification) => $notification->leaveRequest->status === LeaveStatus::Rejected);
        Notification::assertNotSentTo($reviewer, LeaveReviewed::class);
    }

    public function test_claims_and_payroll_notify_the_people_who_act_on_them(): void
    {
        Notification::fake();
        $this->seed(ChartOfAccountsSeeder::class);
        $finance = $this->adminWith(Permission::ClaimsReview, Permission::ClaimsReimburse, Permission::PayrollApprove);
        $preparer = $this->adminWith(Permission::PayrollManage);

        $this->actingAs($this->user, 'web')->post(route('claims.store'), [
            'account_id' => Account::query()->where('code', '6300')->sole()->id,
            'date' => '2026-03-30',
            'description' => 'Grab to client meeting',
            'amount' => '45.80',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($finance, ClaimSubmitted::class);

        $this->actingAs($finance, 'admin')->put(route('admin.claims.review', ExpenseClaim::query()->sole()), ['decision' => 'approve'])->assertRedirect();

        Notification::assertSentTo($this->user, ClaimUpdated::class);

        $this->actingAs($preparer, 'admin')->post(route('admin.payroll.store'), ['period' => '2026-03'])->assertRedirect();

        Notification::assertSentTo($finance, PayrollAwaitingApproval::class);
        Notification::assertNotSentTo($preparer, PayrollAwaitingApproval::class);
        Notification::assertNotSentTo($this->user, PayslipReady::class);

        $this->actingAs($finance, 'admin')->post(route('admin.payroll.approve', PayrollRun::query()->sole()))->assertRedirect();

        Notification::assertSentTo($this->user, PayslipReady::class);
    }

    public function test_an_admin_is_told_when_the_super_admin_changes_their_role(): void
    {
        Notification::fake();
        $owner = User::factory()->superAdmin()->create();
        $admin = $this->adminWith(Permission::EmployeesView);
        $auditor = Role::factory()->create(['name' => 'Auditor']);
        $update = fn (array $data) => $this->actingAs($owner, 'admin')->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'is_active' => '1', ...$data,
        ])->assertSessionHasNoErrors();

        $update(['role' => 'admin', 'admin_role_id' => $admin->admin_role_id]);
        Notification::assertNothingSentTo($admin);

        $update(['role' => 'admin', 'admin_role_id' => $auditor->id]);
        Notification::assertSentTo($admin, AdminRoleChanged::class, fn (AdminRoleChanged $notification) => $notification->role->is($auditor));
    }

    public function test_the_bell_lists_notifications_and_opening_one_marks_it_read(): void
    {
        $leave = LeaveRequest::factory()->for($this->employee)->approved()->create(['start_date' => '2026-04-06', 'end_date' => '2026-04-07', 'days' => 2]);
        $this->user->notify(new LeaveReviewed($leave));
        $notification = $this->user->notifications()->sole();
        $stranger = User::factory()->create();

        $this->actingAs($this->user, 'web')->get(route('dashboard'))
            ->assertOk()
            ->assertSee("Your {$leave->leaveType->name} for 6–7 Apr 2026 was approved.")
            ->assertSee(route('notifications.open', $notification));

        $this->actingAs($stranger, 'web')->get(route('notifications.open', $notification))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($this->user, 'web')->get(route('notifications.open', $notification))->assertRedirect(route('leave.index'));
        $this->assertNotNull($notification->fresh()->read_at);

        $this->user->notify(new LeaveReviewed($leave));
        $this->actingAs($this->user, 'web')->post(route('notifications.read_all'))->assertRedirect();
        $this->assertSame(0, $this->user->unreadNotifications()->count());

        $this->actingAs($this->user, 'web')->get(route('notifications.index'))->assertOk()->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 2);
    }

    public function test_email_follows_the_users_setting_and_language(): void
    {
        Notification::fake();
        $leave = LeaveRequest::factory()->for($this->employee)->create(['status' => LeaveStatus::Rejected, 'start_date' => '2026-04-06', 'end_date' => '2026-04-06', 'days' => 1]);

        $this->user->forceFill(['locale' => 'ms'])->save();
        $this->user->notify(new LeaveReviewed($leave));
        Notification::assertSentTo($this->user, LeaveReviewed::class, fn ($notification, array $channels, $notifiable, $locale) => $channels === ['database', 'mail'] && $locale === 'ms');

        $this->actingAs($this->user, 'web')->put(route('profile.update'), [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'email_notifications' => '0',
        ])->assertSessionHasNoErrors();

        $this->user->refresh()->notify(new LeaveReviewed($leave));
        Notification::assertSentTo($this->user, LeaveReviewed::class, fn ($notification, array $channels) => $channels === ['database']);

        $this->app->setLocale('ms');
        $this->assertSame('Cuti anda telah ditolak', (new LeaveReviewed($leave))->toMail($this->user)->subject);
    }

    protected function adminWith(Permission ...$permissions): User
    {
        return User::factory()->admin()->for(Role::factory()->with(...$permissions), 'adminRole')->create();
    }
}
