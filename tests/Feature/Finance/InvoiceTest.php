<?php

namespace Tests\Feature\Finance;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\FinancialReports;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Contact $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-15 10:00:00');
        $this->seed(ChartOfAccountsSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $this->customer = Contact::factory()->create(['name' => 'Syarikat Maju Sdn Bhd']);
    }

    public function test_regular_users_cannot_reach_finance_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'admin')->get(route('admin.invoices.index'))->assertForbidden();
        $this->actingAs($user, 'admin')->post(route('admin.invoices.store'), [])->assertForbidden();
    }

    public function test_draft_totals_are_worked_out_and_nothing_is_posted(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.store'), $this->payload());

        $invoice = Invoice::query()->sole();
        $response->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertSame('INV-0001', $invoice->number);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame('2500.00', $invoice->subtotal);
        $this->assertSame('200.00', $invoice->tax_amount);
        $this->assertSame('2700.00', $invoice->total);
        $this->assertSame(0, JournalEntry::query()->count());
    }

    public function test_invoice_lines_are_required(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.invoices.store'), [...$this->payload(), 'lines' => []])
            ->assertSessionHasErrors(['lines']);

        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_issuing_posts_receivable_sales_and_tax(): void
    {
        $invoice = $this->draft();

        $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.issue', $invoice))->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Open, $invoice->status);
        $this->assertNotNull($invoice->journal_entry_id);

        $this->assertSame(270000, $this->balance('accounts_receivable'));
        $this->assertSame(250000, $this->balance('sales'));
        $this->assertSame(20000, $this->balance('sales_tax'));
    }

    public function test_payments_move_the_invoice_to_partially_paid_then_paid(): void
    {
        $invoice = $this->issued();
        $bank = Account::system('bank');

        $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.pay', $invoice), [
            'account_id' => $bank->id, 'date' => '2026-06-15', 'amount' => '1000', 'method' => 'bank_transfer',
        ])->assertSessionHasNoErrors();
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->refresh()->status);

        $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.pay', $invoice), [
            'account_id' => $bank->id, 'date' => '2026-06-15', 'amount' => '1700',
        ])->assertSessionHasNoErrors();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
        $this->assertSame(0, $this->balance('accounts_receivable'));
        $this->assertSame(270000, $this->balance('bank'));
    }

    public function test_a_payment_above_the_balance_due_is_refused(): void
    {
        $invoice = $this->issued();

        $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.pay', $invoice), [
            'account_id' => Account::system('bank')->id, 'date' => '2026-06-15', 'amount' => '2700.01',
        ])->assertSessionHasErrorsIn('payment', ['amount']);

        $this->assertSame(InvoiceStatus::Open, $invoice->refresh()->status);
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_voiding_reverses_the_posting_but_only_while_unpaid(): void
    {
        $invoice = $this->issued();

        $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.void', $invoice), ['reason' => 'Wrong customer'])->assertRedirect();

        $this->assertSame(InvoiceStatus::Void, $invoice->refresh()->status);
        $this->assertSame(0, $this->balance('accounts_receivable'));
        $this->assertSame(0, $this->balance('sales'));

        $paid = $this->issued();
        $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.pay', $paid), [
            'account_id' => Account::system('bank')->id, 'date' => '2026-06-15', 'amount' => '100',
        ]);

        $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.void', $paid))->assertSessionHasErrors('delete');
        $this->assertSame(InvoiceStatus::PartiallyPaid, $paid->refresh()->status);
    }

    public function test_issued_invoices_cannot_be_edited_or_deleted(): void
    {
        $invoice = $this->issued();

        $this->actingAs($this->admin, 'admin')->get(route('admin.invoices.edit', $invoice))->assertRedirect(route('admin.invoices.show', $invoice));
        $this->actingAs($this->admin, 'admin')->delete(route('admin.invoices.destroy', $invoice))->assertSessionHasErrors('delete');

        $this->assertModelExists($invoice);
    }

    public function test_bills_have_no_tax_and_post_to_payables(): void
    {
        $supplier = Contact::factory()->supplier()->create();
        $rent = Account::query()->where('code', '6100')->sole();

        $this->actingAs($this->admin, 'admin')->post(route('admin.bills.store'), [
            ...$this->payload(),
            'contact_id' => $supplier->id,
            'lines' => [['account_id' => $rent->id, 'description' => 'June rent', 'quantity' => 1, 'unit_price' => '3000']],
        ]);

        $bill = Invoice::query()->ofType(InvoiceType::Purchase)->sole();
        $this->assertSame('BILL-0001', $bill->number);
        $this->assertSame('0.00', $bill->tax_amount);

        $this->actingAs($this->admin, 'admin')->post(route('admin.bills.issue', $bill));

        $this->assertSame(300000, $this->balance('accounts_payable'));
        $this->assertSame(300000, app(FinancialReports::class)->balances()[$rent->id]);
    }

    public function test_an_invoice_is_not_reachable_through_the_bills_pages(): void
    {
        $invoice = $this->draft();

        $this->actingAs($this->admin, 'admin')->get(route('admin.bills.show', $invoice))->assertNotFound();
    }

    public function test_the_invoice_page_shows_the_document(): void
    {
        $invoice = $this->issued();

        $this->actingAs($this->admin, 'admin')->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Syarikat Maju Sdn Bhd')
            ->assertSee('RM 2,700.00')
            ->assertSee('Record payment');
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        $sales = Account::system('sales');

        return [
            'contact_id' => $this->customer->id,
            'issue_date' => '2026-06-15',
            'due_date' => '2026-07-15',
            'tax_rate' => '8',
            'lines' => [
                ['account_id' => $sales->id, 'description' => 'Consulting', 'quantity' => '10', 'unit_price' => '200'],
                ['account_id' => $sales->id, 'description' => 'Setup fee', 'quantity' => '1', 'unit_price' => '500'],
            ],
        ];
    }

    protected function draft(): Invoice
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.store'), $this->payload());

        return Invoice::query()->latest('id')->first();
    }

    protected function issued(): Invoice
    {
        $invoice = $this->draft();
        $this->actingAs($this->admin, 'admin')->post(route('admin.invoices.issue', $invoice));

        return $invoice->refresh();
    }

    protected function balance(string $key): int
    {
        return app(FinancialReports::class)->balances()[Account::system($key)->id];
    }
}
