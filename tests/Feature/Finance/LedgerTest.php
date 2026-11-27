<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\FinancialReports;
use App\Services\Ledger;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-15 10:00:00');
        $this->seed(ChartOfAccountsSeeder::class);
        $this->admin = User::factory()->superAdmin()->create();
    }

    public function test_a_balanced_manual_entry_is_posted(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.journal_entries.store'), [
            'date' => '2026-06-01',
            'description' => 'Owner capital',
            'lines' => [
                ['account_id' => Account::system('bank')->id, 'debit' => '50000'],
                ['account_id' => Account::system('capital')->id, 'credit' => '50000'],
                ['account_id' => '', 'debit' => '', 'credit' => ''],
            ],
        ]);

        $entry = JournalEntry::query()->sole();
        $response->assertRedirect(route('admin.journal_entries.show', $entry));

        $this->assertSame('JE-000001', $entry->number);
        $this->assertSame($this->admin->id, $entry->created_by);
        $this->assertCount(2, $entry->lines);
    }

    public function test_an_unbalanced_entry_is_refused(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.journal_entries.store'), [
            'date' => '2026-06-01',
            'description' => 'Typo',
            'lines' => [
                ['account_id' => Account::system('bank')->id, 'debit' => '500'],
                ['account_id' => Account::system('capital')->id, 'credit' => '50'],
            ],
        ])->assertSessionHasErrors('lines');

        $this->assertSame(0, JournalEntry::query()->count());
    }

    public function test_the_ledger_service_refuses_unbalanced_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(Ledger::class)->post(today(), 'Broken', [
            ['account' => 'bank', 'debit' => 100],
            ['account' => 'capital', 'credit' => 99],
        ]);
    }

    public function test_reversing_a_manual_entry_cancels_it_out(): void
    {
        $entry = app(Ledger::class)->post(today(), 'Owner capital', [
            ['account' => 'bank', 'debit' => 1_000_000],
            ['account' => 'capital', 'credit' => 1_000_000],
        ]);

        $this->actingAs($this->admin, 'admin')->post(route('admin.journal_entries.reverse', $entry))->assertRedirect();

        $reversal = JournalEntry::query()->where('reverses_id', $entry->id)->sole();
        $this->assertSame(0, app(FinancialReports::class)->balances()[Account::system('bank')->id]);

        // A reversal cannot itself be reversed, and the original cannot be reversed twice.
        $this->actingAs($this->admin, 'admin')->post(route('admin.journal_entries.reverse', $reversal))->assertSessionHasErrors('delete');
        $this->actingAs($this->admin, 'admin')->post(route('admin.journal_entries.reverse', $entry))->assertSessionHasErrors('delete');
        $this->assertSame(2, JournalEntry::query()->count());
    }

    public function test_reports_balance_and_show_profit(): void
    {
        $ledger = app(Ledger::class);
        $ledger->post(today(), 'Capital', [['account' => 'bank', 'debit' => 1_000_000], ['account' => 'capital', 'credit' => 1_000_000]]);
        $ledger->post(today(), 'Cash sale', [['account' => 'bank', 'debit' => 300_000], ['account' => 'sales', 'credit' => 300_000]]);
        $ledger->post(today(), 'Salaries', [['account' => 'salary_expense', 'debit' => 120_000], ['account' => 'bank', 'credit' => 120_000]]);

        $reports = app(FinancialReports::class);
        $trial = $reports->trialBalance(today());
        $sheet = $reports->balanceSheet(today());
        $profit = $reports->profitAndLoss(today()->startOfMonth(), today());

        $this->assertSame($trial['debit'], $trial['credit']);
        $this->assertSame(180_000, $profit['net']);
        $this->assertSame(1_180_000, $sheet['total_assets']);
        $this->assertSame($sheet['total_assets'], $sheet['total_liabilities'] + $sheet['total_equity']);

        $this->actingAs($this->admin, 'admin')->get(route('admin.reports.profit_and_loss'))->assertOk()->assertSee('Net profit')->assertSee('RM 1,800.00');
        $this->actingAs($this->admin, 'admin')->get(route('admin.reports.balance_sheet'))->assertOk()->assertSee('Assets equal liabilities plus equity.');
        $this->actingAs($this->admin, 'admin')->get(route('admin.reports.trial_balance'))->assertOk()->assertSee('Balanced');
        $this->actingAs($this->admin, 'admin')->get(route('admin.reports.general_ledger', ['account' => Account::system('bank')->id]))->assertOk()->assertSee('RM 11,800.00');
    }

    public function test_system_accounts_cannot_be_deleted_or_retyped(): void
    {
        $bank = Account::system('bank');

        $this->actingAs($this->admin, 'admin')->delete(route('admin.accounts.destroy', $bank))->assertSessionHasErrors('delete');
        $this->actingAs($this->admin, 'admin')->put(route('admin.accounts.update', $bank), [
            'code' => $bank->code, 'name' => 'Main bank', 'type' => 'expense', 'is_active' => '0',
        ]);

        $bank->refresh();
        $this->assertSame('asset', $bank->type->value);
        $this->assertTrue($bank->is_active);
        $this->assertModelExists($bank);
    }
}
