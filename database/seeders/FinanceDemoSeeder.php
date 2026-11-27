<?php

namespace Database\Seeders;

use App\Enums\ClaimStatus;
use App\Enums\ContactType;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ExpenseClaimPosting;
use App\Services\InvoicePosting;
use App\Services\Ledger;
use App\Support\DocumentNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Three months of demo bookkeeping: opening capital, customers, suppliers, invoices, bills and claims.
 */
class FinanceDemoSeeder extends Seeder
{
    public function __construct(
        protected Ledger $ledger,
        protected InvoicePosting $invoices,
        protected ExpenseClaimPosting $claims,
    ) {}

    public function run(): void
    {
        $start = today()->subMonths(3)->startOfMonth();
        $bank = Account::system('bank');

        $this->ledger->post($start, 'Opening capital', [
            ['account' => $bank, 'debit' => 500_000_00],
            ['account' => 'capital', 'credit' => 500_000_00],
        ], reference: 'Capital');

        $customers = collect(['Syarikat Maju Jaya Sdn Bhd', 'Kedai Runcit Pak Ali', 'Pelangi Digital Sdn Bhd', 'Borneo Logistics Bhd', 'Hikmah Consulting'])
            ->map(fn (string $name) => Contact::factory()->create(['name' => $name, 'type' => ContactType::Customer]));

        $suppliers = collect([
            'rent' => 'Menara Holdings Sdn Bhd',
            'utilities' => 'Tenaga Nasional Berhad',
            'software' => 'CloudSoft Asia Sdn Bhd',
            'supplies' => 'Stationery World Enterprise',
        ])->map(fn (string $name) => Contact::factory()->supplier()->create(['name' => $name]));

        $this->seedInvoices($customers, $start, $bank);
        $this->seedBills($suppliers, $start, $bank);
        $this->seedClaims($bank);
    }

    /**
     * @param  Collection<int, Contact>  $customers
     */
    protected function seedInvoices($customers, Carbon $start, Account $bank): void
    {
        $service = Account::query()->where('code', '4100')->firstOrFail();
        $sales = Account::system('sales');

        $plan = [
            // [days after start, customer, description, qty, price, payment: full|part|none|draft]
            [5, 0, 'Website redesign', 1, 18_000, 'full'],
            [12, 1, 'POS system setup', 1, 4_500, 'full'],
            [20, 2, 'Monthly retainer', 1, 6_000, 'full'],
            [35, 3, 'Fleet tracking dashboard', 1, 22_000, 'part'],
            [41, 2, 'Monthly retainer', 1, 6_000, 'full'],
            [55, 4, 'Process review workshop', 2, 3_500, 'none'],
            [70, 2, 'Monthly retainer', 1, 6_000, 'none'],
            [78, 0, 'Maintenance hours', 24, 180, 'none'],
            [85, 1, 'Barcode scanners', 4, 650, 'draft'],
        ];

        foreach ($plan as [$offset, $customer, $description, $quantity, $price, $payment]) {
            $issued = $start->copy()->addDays($offset)->min(today());

            $invoice = $this->invoices->saveDraft(new Invoice, InvoiceType::Sales, [
                'contact_id' => $customers[$customer]->id,
                'issue_date' => $issued->toDateString(),
                'due_date' => $issued->copy()->addDays(30)->toDateString(),
                'tax_rate' => 8,
                'lines' => [[
                    'account_id' => $description === 'Barcode scanners' ? $sales->id : $service->id,
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                ]],
            ]);

            if ($payment === 'draft') {
                continue;
            }

            $this->invoices->issue($invoice);

            $paidOn = $issued->copy()->addDays(fake()->numberBetween(7, 25))->min(today());

            match ($payment) {
                'full' => $this->invoices->recordPayment($invoice, $bank, $paidOn, $invoice->balanceDueCents(), PaymentMethod::BankTransfer, 'IBG ' . fake()->numerify('######')),
                'part' => $this->invoices->recordPayment($invoice, $bank, $paidOn, (int) round($invoice->balanceDueCents() / 2), PaymentMethod::BankTransfer, 'Deposit'),
                default => null,
            };
        }
    }

    /**
     * @param  Collection<string, Contact>  $suppliers
     */
    protected function seedBills($suppliers, Carbon $start, Account $bank): void
    {
        $account = fn (string $code) => Account::query()->where('code', $code)->firstOrFail();

        for ($month = 0; $month < 3; $month++) {
            $date = $start->copy()->addMonths($month)->startOfMonth()->addDays(1);
            $lastMonth = $month === 2;

            foreach ([
                ['rent', '6100', 'Office rent', 4_200],
                ['utilities', '6200', 'Electricity', fake()->numberBetween(380, 620)],
                ['software', '6700', 'Cloud hosting and licences', 1_150],
            ] as [$supplier, $code, $description, $price]) {
                $bill = $this->invoices->saveDraft(new Invoice, InvoiceType::Purchase, [
                    'contact_id' => $suppliers[$supplier]->id,
                    'issue_date' => $date->toDateString(),
                    'due_date' => $date->copy()->addDays(14)->toDateString(),
                    'reference' => strtoupper(substr($supplier, 0, 3)) . '-' . fake()->numerify('#####'),
                    'lines' => [['account_id' => $account($code)->id, 'description' => $description, 'quantity' => 1, 'unit_price' => $price]],
                ]);
                $this->invoices->issue($bill);

                if (! $lastMonth || $supplier === 'rent') {
                    $this->invoices->recordPayment($bill, $bank, $date->copy()->addDays(10)->min(today()), $bill->balanceDueCents(), PaymentMethod::BankTransfer);
                }
            }
        }

        $supplies = $this->invoices->saveDraft(new Invoice, InvoiceType::Purchase, [
            'contact_id' => $suppliers['supplies']->id,
            'issue_date' => today()->subDays(40)->toDateString(),
            'due_date' => today()->subDays(10)->toDateString(),
            'reference' => 'SW-' . fake()->numerify('####'),
            'lines' => [
                ['account_id' => $account('6500')->id, 'description' => 'A4 paper (boxes)', 'quantity' => 10, 'unit_price' => 120],
                ['account_id' => $account('6500')->id, 'description' => 'Toner cartridges', 'quantity' => 3, 'unit_price' => 285],
            ],
        ]);
        $this->invoices->issue($supplies);
    }

    protected function seedClaims(Account $bank): void
    {
        $admin = User::query()->where('email', config('admin.email'))->firstOrFail();
        $employees = Employee::query()->active()->inRandomOrder()->take(6)->get();
        $demo = Employee::query()->whereHas('user', fn ($query) => $query->where('email', 'user@example.com'))->first();
        $employees = $demo ? $employees->prepend($demo)->unique('id') : $employees;
        $categories = Account::query()->whereIn('code', ['6300', '6400', '6500', '6700'])->get();

        foreach ($employees as $index => $employee) {
            $claim = new ExpenseClaim([
                'account_id' => $categories->random()->id,
                'date' => today()->subDays(fake()->numberBetween(2, 30)),
                'description' => fake()->randomElement(['Grab to client meeting', 'Printer ink', 'Client lunch', 'Parking at KLCC', 'Domain renewal', 'Toll and petrol']),
                'amount' => fake()->randomFloat(2, 15, 380),
            ]);
            $claim->employee()->associate($employee);
            $claim->number = DocumentNumber::next(ExpenseClaim::query(), 'CLM-');
            $claim->status = ClaimStatus::Pending;
            $claim->save();

            // One in three stays pending, one is approved, one is approved and paid back.
            if ($index % 3 === 0) {
                continue;
            }

            $this->claims->approve($claim, $admin);

            if ($index % 3 === 2) {
                $this->claims->reimburse($claim->refresh(), $bank, today()->subDay());
            }
        }
    }
}
