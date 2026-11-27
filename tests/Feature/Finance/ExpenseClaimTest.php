<?php

namespace Tests\Feature\Finance;

use App\Enums\ClaimStatus;
use App\Models\Account;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\User;
use App\Services\FinancialReports;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseClaimTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Employee $employee;

    protected Account $travel;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-15 10:00:00');
        Storage::fake('local');
        $this->seed(ChartOfAccountsSeeder::class);

        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
        $this->travel = Account::query()->where('code', '6300')->sole();
    }

    public function test_employee_can_send_a_claim_with_a_receipt(): void
    {
        $this->actingAs($this->user)->post(route('claims.store'), [
            'account_id' => $this->travel->id,
            'date' => '2026-06-10',
            'description' => 'Grab to client meeting',
            'amount' => '45.80',
            'receipt' => UploadedFile::fake()->create('grab.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('claims.index'));

        $claim = $this->employee->expenseClaims()->sole();
        $this->assertSame('CLM-0001', $claim->number);
        $this->assertSame(ClaimStatus::Pending, $claim->status);
        $this->assertSame('grab.pdf', $claim->receipt_name);
        Storage::disk('local')->assertExists($claim->receipt_path);
    }

    public function test_claims_older_than_90_days_or_on_system_accounts_are_refused(): void
    {
        $this->actingAs($this->user)->post(route('claims.store'), [
            'account_id' => Account::system('salary_expense')->id,
            'date' => '2026-03-01',
            'description' => 'Old',
            'amount' => '10',
        ])->assertSessionHasErrorsIn('claim', ['account_id', 'date']);

        $this->assertSame(0, ExpenseClaim::query()->count());
    }

    public function test_employees_cannot_open_or_cancel_someone_elses_claim(): void
    {
        $other = ExpenseClaim::factory()->create(['account_id' => $this->travel->id, 'receipt_path' => 'receipts/x.pdf', 'receipt_name' => 'x.pdf']);
        Storage::disk('local')->put('receipts/x.pdf', 'pdf');

        $this->actingAs($this->user)->get(route('claims.receipt', $other))->assertForbidden();
        $this->actingAs($this->user)->put(route('claims.cancel', $other))->assertForbidden();

        $this->assertSame(ClaimStatus::Pending, $other->refresh()->status);
    }

    public function test_approving_posts_the_expense_and_paying_back_clears_it(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $claim = ExpenseClaim::factory()->for($this->employee)->create(['account_id' => $this->travel->id, 'amount' => '120.00']);
        $bank = Account::system('bank');

        $this->actingAs($admin, 'admin')->put(route('admin.claims.review', $claim), ['decision' => 'approve'])->assertRedirect();

        $claim->refresh();
        $this->assertSame(ClaimStatus::Approved, $claim->status);
        $this->assertSame($admin->id, $claim->reviewed_by);
        $balances = app(FinancialReports::class)->balances();
        $this->assertSame(12000, $balances[$this->travel->id]);
        $this->assertSame(12000, $balances[Account::system('claims_payable')->id]);

        $this->actingAs($admin, 'admin')->post(route('admin.claims.reimburse', $claim), ['account_id' => $bank->id, 'date' => '2026-06-15'])->assertRedirect();

        $this->assertSame(ClaimStatus::Reimbursed, $claim->refresh()->status);
        $balances = app(FinancialReports::class)->balances();
        $this->assertSame(0, $balances[Account::system('claims_payable')->id]);
        $this->assertSame(-12000, $balances[$bank->id]);
    }

    public function test_rejecting_needs_a_note_and_posts_nothing(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $claim = ExpenseClaim::factory()->for($this->employee)->create(['account_id' => $this->travel->id]);

        $this->actingAs($admin, 'admin')->put(route('admin.claims.review', $claim), ['decision' => 'reject'])->assertSessionHasErrors('review_note');
        $this->actingAs($admin, 'admin')->put(route('admin.claims.review', $claim), ['decision' => 'reject', 'review_note' => 'No receipt'])->assertRedirect();

        $this->assertSame(ClaimStatus::Rejected, $claim->refresh()->status);
        $this->assertNull($claim->journal_entry_id);

        // Reviewed claims cannot be reviewed again or cancelled by the employee.
        $this->actingAs($admin, 'admin')->put(route('admin.claims.review', $claim), ['decision' => 'approve'])->assertSessionHasErrors('review');
        $this->actingAs($this->user, 'web')->put(route('claims.cancel', $claim))->assertSessionHasErrors('delete');
    }
}
