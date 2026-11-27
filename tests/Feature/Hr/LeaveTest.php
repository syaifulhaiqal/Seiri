<?php

namespace Tests\Feature\Hr;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Employee $employee;

    protected LeaveType $annual;

    protected function setUp(): void
    {
        parent::setUp();

        // A Wednesday, so "next Monday" etc. are predictable.
        Carbon::setTestNow('2026-03-04 10:00:00');

        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
        $this->annual = LeaveType::factory()->create(['name' => 'Annual leave', 'days_per_year' => 5]);
    }

    public function test_working_days_skip_weekends(): void
    {
        // Fri 6 Mar to Tue 10 Mar 2026: Fri, Mon, Tue.
        $this->assertSame(3, LeaveRequest::workingDays(Carbon::parse('2026-03-06'), Carbon::parse('2026-03-10')));
        $this->assertSame(0, LeaveRequest::workingDays(Carbon::parse('2026-03-07'), Carbon::parse('2026-03-08')));
    }

    public function test_employee_can_apply_and_the_days_count_working_days_only(): void
    {
        $this->actingAs($this->user)->post(route('leave.store'), [
            'leave_type_id' => $this->annual->id,
            'start_date' => '2026-03-06',
            'end_date' => '2026-03-10',
            'reason' => 'Family trip',
        ])->assertRedirect(route('leave.index'));

        $leave = $this->employee->leaveRequests()->sole();
        $this->assertSame(3, $leave->days);
        $this->assertSame(LeaveStatus::Pending, $leave->status);

        $this->actingAs($this->user)->get(route('leave.index'))->assertOk()->assertSee('6–10 Mar 2026')->assertSee('Pending');
    }

    public function test_requests_beyond_the_balance_or_overlapping_are_refused(): void
    {
        LeaveRequest::factory()->for($this->employee)->for($this->annual)->approved()->create([
            'start_date' => '2026-03-09', 'end_date' => '2026-03-12', 'days' => 4,
        ]);

        // 1 day left; asking for 2.
        $this->actingAs($this->user)->post(route('leave.store'), [
            'leave_type_id' => $this->annual->id,
            'start_date' => '2026-03-16',
            'end_date' => '2026-03-17',
        ])->assertSessionHasErrorsIn('apply', 'end_date');

        // Overlaps the approved leave.
        $this->actingAs($this->user)->post(route('leave.store'), [
            'leave_type_id' => $this->annual->id,
            'start_date' => '2026-03-12',
            'end_date' => '2026-03-12',
        ])->assertSessionHasErrorsIn('apply', 'start_date');

        // Weekend only.
        $this->actingAs($this->user)->post(route('leave.store'), [
            'leave_type_id' => $this->annual->id,
            'start_date' => '2026-03-14',
            'end_date' => '2026-03-15',
        ])->assertSessionHasErrorsIn('apply', 'end_date');

        $this->assertSame(1, $this->employee->leaveRequests()->count());
    }

    public function test_unlimited_leave_types_have_no_balance_check(): void
    {
        $unpaid = LeaveType::factory()->unlimited()->create(['name' => 'Unpaid leave']);

        $this->actingAs($this->user)->post(route('leave.store'), [
            'leave_type_id' => $unpaid->id,
            'start_date' => '2026-03-09',
            'end_date' => '2026-03-27',
        ])->assertSessionHasNoErrors();

        $this->assertSame(15, $this->employee->leaveRequests()->sole()->days);
    }

    public function test_employee_can_cancel_only_their_own_pending_request(): void
    {
        $mine = LeaveRequest::factory()->for($this->employee)->for($this->annual)->create();
        $theirs = LeaveRequest::factory()->for(Employee::factory())->for($this->annual)->create();

        $this->actingAs($this->user)->put(route('leave.cancel', $theirs))->assertForbidden();
        $this->actingAs($this->user)->put(route('leave.cancel', $mine))->assertRedirect(route('leave.index'));

        $this->assertSame(LeaveStatus::Cancelled, $mine->fresh()->status);
        $this->assertSame(LeaveStatus::Pending, $theirs->fresh()->status);
    }

    public function test_users_without_an_employee_record_see_a_notice_and_cannot_apply(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('leave.index'))->assertOk()->assertSee('not linked to an employee record');
        $this->actingAs($stranger)->post(route('leave.store'), [
            'leave_type_id' => $this->annual->id,
            'start_date' => '2026-03-09',
            'end_date' => '2026-03-09',
        ])->assertForbidden();
    }

    public function test_admin_can_approve_and_reject_with_a_reason(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $first = LeaveRequest::factory()->for($this->employee)->for($this->annual)->create();
        $second = LeaveRequest::factory()->for($this->employee)->for($this->annual)->create();

        $this->actingAs($admin, 'admin')->get(route('admin.leave_requests.show', $first))->assertOk()->assertSee('Approve');

        $this->actingAs($admin, 'admin')->put(route('admin.leave_requests.review', $first), ['decision' => 'approve'])
            ->assertRedirect();
        $this->actingAs($admin, 'admin')->put(route('admin.leave_requests.review', $second), ['decision' => 'reject'])
            ->assertSessionHasErrors('review_note');
        $this->actingAs($admin, 'admin')->put(route('admin.leave_requests.review', $second), ['decision' => 'reject', 'review_note' => 'Month-end closing'])
            ->assertRedirect();

        $first->refresh();
        $this->assertSame(LeaveStatus::Approved, $first->status);
        $this->assertTrue($first->reviewer->is($admin));
        $this->assertSame(LeaveStatus::Rejected, $second->fresh()->status);

        // A decided request cannot be decided again.
        $this->actingAs($admin, 'admin')->put(route('admin.leave_requests.review', $first), ['decision' => 'reject', 'review_note' => 'Changed my mind'])
            ->assertSessionHasErrors('review');
        $this->assertSame(LeaveStatus::Approved, $first->fresh()->status);
    }

    public function test_admin_cannot_delete_a_leave_type_in_use(): void
    {
        $admin = User::factory()->superAdmin()->create();
        LeaveRequest::factory()->for($this->employee)->for($this->annual)->create();

        $this->actingAs($admin, 'admin')->delete(route('admin.leave_types.destroy', $this->annual))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($this->annual);
    }
}
