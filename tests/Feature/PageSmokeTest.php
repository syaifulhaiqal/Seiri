<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinanceDemoSeeder;
use Database\Seeders\HrDemoSeeder;
use Database\Seeders\LeaveTypeSeeder;
use Database\Seeders\PayrollDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every page renders with the local demo data, so a broken view is caught before anyone opens it.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([AdminUserSeeder::class, LeaveTypeSeeder::class, ChartOfAccountsSeeder::class]);
        User::factory()->create(['email' => 'user@example.com']);
        $this->seed([HrDemoSeeder::class, FinanceDemoSeeder::class, PayrollDemoSeeder::class]);
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::query()->where('email', config('admin.email'))->sole();
        $run = PayrollRun::query()->latest('period')->first();

        $urls = [
            route('admin.dashboard'),
            route('admin.employees.show', Employee::query()->first()),
            route('admin.employees.edit', Employee::query()->first()),
            route('admin.payroll.index'),
            route('admin.payroll.show', $run),
            route('admin.payroll.payslip', [$run, $run->payslips()->first()]),
            route('admin.claims.index'),
            route('admin.claims.show', ExpenseClaim::query()->first()),
            route('admin.invoices.index'),
            route('admin.invoices.create'),
            route('admin.invoices.show', Invoice::query()->ofType(InvoiceType::Sales)->first()),
            route('admin.invoices.edit', Invoice::query()->ofType(InvoiceType::Sales)->where('status', 'draft')->first()),
            route('admin.bills.index'),
            route('admin.bills.create'),
            route('admin.bills.show', Invoice::query()->ofType(InvoiceType::Purchase)->first()),
            route('admin.contacts.index'),
            route('admin.contacts.create'),
            route('admin.contacts.edit', Contact::query()->first()),
            route('admin.journal_entries.index'),
            route('admin.journal_entries.create'),
            route('admin.journal_entries.show', JournalEntry::query()->whereNotNull('source_type')->first()),
            route('admin.accounts.index'),
            route('admin.accounts.create'),
            route('admin.accounts.edit', Account::system('bank')),
            route('admin.reports.trial_balance'),
            route('admin.reports.profit_and_loss'),
            route('admin.reports.balance_sheet'),
            route('admin.reports.general_ledger'),
            route('admin.audit.index'),
            route('admin.audit.show', AuditLog::query()->where('event', 'updated')->first() ?? AuditLog::query()->first()),
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin, 'admin')->get($url)->assertOk();
        }
    }

    public function test_employee_pages_render(): void
    {
        $user = User::query()->where('email', 'user@example.com')->sole();
        $payslip = $user->employee->payslips()->whereHas('payrollRun', fn ($query) => $query->where('status', '!=', 'draft'))->first();

        foreach ([route('dashboard'), route('payslips.index'), route('payslips.show', $payslip), route('claims.index')] as $url) {
            $this->actingAs($user, 'web')->get($url)->assertOk();
        }
    }
}
