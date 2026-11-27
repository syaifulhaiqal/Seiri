<?php

namespace Tests\Feature\Hr;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\ClaimStatus;
use App\Enums\LeaveStatus;
use App\Enums\Permission;
use App\Models\Account;
use App\Models\Approval;
use App\Models\Approver;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ApprovalRequested;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveReviewed;
use App\Services\ApprovalFlow;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApprovalChainTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Employee $employee;

    protected User $lead;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-03-04 10:00:00');
        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
        $this->lead = Employee::factory()->create(['user_id' => User::factory()])->user;
        $this->manager = Employee::factory()->create(['user_id' => User::factory()])->user;
    }

    public function test_leave_goes_through_each_approver_in_order(): void
    {
        Notification::fake();
        $this->chain(ApprovalType::Leave);
        $hr = User::factory()->admin()->for(Role::factory()->with(Permission::LeaveReview), 'adminRole')->create();

        $this->actingAs($this->user, 'web')->post(route('leave.store'), [
            'leave_type_id' => LeaveType::factory()->create()->id,
            'start_date' => '2026-03-09',
            'end_date' => '2026-03-10',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($this->lead, ApprovalRequested::class);
        Notification::assertNotSentTo($this->manager, ApprovalRequested::class);
        Notification::assertNotSentTo($hr, LeaveRequested::class);

        $leave = LeaveRequest::query()->sole();
        [$first, $second] = $leave->approvals->all();

        // The manager's turn comes only after the team lead.
        $this->actingAs($this->manager, 'web')->put(route('approvals.decide', $second), ['decision' => 'approve'])->assertSessionHasErrors('review');

        $this->actingAs($this->lead, 'web')->get(route('approvals.index'))->assertOk()->assertSee($this->employee->name);
        $this->actingAs($this->lead, 'web')->put(route('approvals.decide', $first), ['decision' => 'approve'])->assertSessionHasNoErrors();

        Notification::assertSentTo($this->manager, ApprovalRequested::class);
        $this->assertSame(LeaveStatus::Pending, $leave->fresh()->status);

        $this->actingAs($this->manager, 'web')->put(route('approvals.decide', $second), ['decision' => 'approve'])->assertSessionHasNoErrors();

        $leave->refresh();
        $this->assertSame(LeaveStatus::Approved, $leave->status);
        $this->assertTrue($leave->reviewer->is($this->manager));
        Notification::assertSentTo($this->user, LeaveReviewed::class);
    }

    public function test_a_rejection_ends_the_chain_and_posts_nothing(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
        $this->chain(ApprovalType::Claim);

        $this->actingAs($this->user, 'web')->post(route('claims.store'), [
            'account_id' => Account::query()->where('code', '6300')->sole()->id,
            'date' => '2026-03-02',
            'description' => 'Taxi',
            'amount' => '30.00',
        ])->assertSessionHasNoErrors();

        $claim = $this->employee->expenseClaims()->sole();
        $first = $claim->approvals->first();

        $this->actingAs($this->lead, 'web')->put(route('approvals.decide', $first), ['decision' => 'reject'])->assertSessionHasErrors('note');
        $this->actingAs($this->lead, 'web')->put(route('approvals.decide', $first), ['decision' => 'reject', 'note' => 'Not a work trip'])->assertSessionHasNoErrors();

        $this->assertSame(ClaimStatus::Rejected, $claim->fresh()->status);
        $this->assertNull($claim->fresh()->journal_entry_id);
        $this->assertSame([ApprovalStatus::Rejected, ApprovalStatus::Skipped], $claim->approvals()->pluck('status')->all());
    }

    public function test_hr_can_still_decide_directly_and_strangers_cannot_decide(): void
    {
        $this->chain(ApprovalType::Leave);
        $leave = LeaveRequest::factory()->for($this->employee)->create(['start_date' => '2026-03-09', 'end_date' => '2026-03-09', 'days' => 1]);
        app(ApprovalFlow::class)->start($leave);
        $stranger = Employee::factory()->create(['user_id' => User::factory()])->user;

        $this->actingAs($stranger, 'web')->put(route('approvals.decide', $leave->approvals->first()), ['decision' => 'approve'])->assertForbidden();

        $hr = User::factory()->superAdmin()->create();
        $this->actingAs($hr, 'admin')->put(route('admin.leave_requests.review', $leave), ['decision' => 'approve'])->assertRedirect();

        $this->assertSame(LeaveStatus::Approved, $leave->fresh()->status);
        $this->assertSame(0, Approval::query()->pending()->count());
    }

    public function test_hr_sets_up_an_employees_approvers(): void
    {
        $hr = User::factory()->admin()->for(Role::factory()->with(Permission::EmployeesManage), 'adminRole')->create();

        $this->actingAs($hr, 'admin')->get(route('admin.employees.approvers.edit', $this->employee))->assertOk()->assertSee($this->lead->employee->name);

        $this->actingAs($hr, 'admin')->put(route('admin.employees.approvers.update', $this->employee), [
            'approvers' => ['leave' => [1 => $this->lead->id, 2 => $this->lead->id]],
        ])->assertSessionHasErrors('approvers.leave');

        $this->actingAs($hr, 'admin')->put(route('admin.employees.approvers.update', $this->employee), [
            'approvers' => ['leave' => [1 => '', 2 => $this->manager->id], 'claim' => [1 => $this->lead->id]],
        ])->assertRedirect(route('admin.employees.show', $this->employee));

        $this->assertSame([1 => $this->manager->id], $this->employee->approvers()->where('type', 'leave')->pluck('approver_id', 'level')->all());
        $this->assertSame(1, $this->employee->approvers()->where('type', 'claim')->count());

        $this->actingAs($hr, 'admin')->put(route('admin.employees.approvers.update', $this->employee), [
            'approvers' => ['leave' => [1 => $this->user->id]],
        ])->assertSessionHasErrors('approvers.leave.1');
    }

    protected function chain(ApprovalType $type): void
    {
        Approver::factory()->for($this->employee)->create(['type' => $type, 'level' => 1, 'approver_id' => $this->lead->id]);
        Approver::factory()->for($this->employee)->create(['type' => $type, 'level' => 2, 'approver_id' => $this->manager->id]);
    }
}
