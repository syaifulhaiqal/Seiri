<?php

namespace Tests\Feature\Sales;

use App\Enums\ContactType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeadStatus;
use App\Enums\Permission;
use App\Enums\QuotationStatus;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
        $this->sales = User::factory()->admin()->for(Role::factory()->with(Permission::CrmManage, Permission::InvoicesManage), 'adminRole')->create();
    }

    public function test_a_lead_becomes_a_customer_is_quoted_and_invoiced(): void
    {
        $this->actingAs($this->sales, 'admin')->post(route('admin.leads.store'), [
            'title' => 'Payroll outsourcing',
            'contact_name' => 'Aisyah Rahman',
            'company' => 'Syarikat Maju Sdn Bhd',
            'email' => 'aisyah@maju.test',
            'status' => 'new',
            'value' => '12000',
        ])->assertRedirect();

        $lead = Lead::query()->sole();
        $this->actingAs($this->sales, 'admin')->post(route('admin.leads.activity', $lead), [
            'type' => 'call', 'happened_at' => now()->format('Y-m-d H:i'), 'summary' => 'Wants a demo next week',
        ])->assertRedirect();
        $this->actingAs($this->sales, 'admin')->get(route('admin.leads.show', $lead))->assertOk()->assertSee('Wants a demo next week');

        $this->actingAs($this->sales, 'admin')->post(route('admin.leads.convert', $lead))->assertRedirect();
        $customer = $lead->refresh()->contact;
        $this->assertSame('Syarikat Maju Sdn Bhd', $customer->name);
        $this->assertSame(ContactType::Customer, $customer->type);

        $product = Product::factory()->create(['name' => 'Payroll service (per staff)', 'sale_price' => '25.00']);
        $this->actingAs($this->sales, 'admin')->get(route('admin.quotations.create', ['lead' => $lead->id]))->assertOk()->assertSee('Payroll service (per staff)');
        $this->actingAs($this->sales, 'admin')->post(route('admin.quotations.store'), [
            'contact_id' => $customer->id,
            'lead_id' => $lead->id,
            'issue_date' => today()->toDateString(),
            'valid_until' => today()->addDays(14)->toDateString(),
            'tax_rate' => '8',
            'lines' => [['product_id' => $product->id, 'description' => 'Payroll service', 'quantity' => 40, 'unit_price' => '25.00']],
        ])->assertRedirect();

        $quotation = Quotation::query()->sole();
        $this->assertSame('QT-0001', $quotation->number);
        $this->assertSame('1000.00', $quotation->subtotal);
        $this->assertSame('1080.00', $quotation->total);
        $this->actingAs($this->sales, 'admin')->get(route('admin.quotations.edit', $quotation))->assertOk()->assertSee('Payroll service');
        $this->actingAs($this->sales, 'admin')->get(route('admin.leads.edit', $lead))->assertOk();

        $this->actingAs($this->sales, 'admin')->post(route('admin.quotations.mark', $quotation), ['status' => 'accepted'])->assertSessionHasErrors('delete');
        $this->actingAs($this->sales, 'admin')->post(route('admin.quotations.mark', $quotation), ['status' => 'sent'])->assertRedirect();
        $this->actingAs($this->sales, 'admin')->get(route('admin.quotations.show', $quotation))->assertOk()->assertSee('Syarikat Maju Sdn Bhd');

        $this->actingAs($this->sales, 'admin')->post(route('admin.quotations.invoice', $quotation))->assertRedirect();

        $invoice = Invoice::query()->sole();
        $this->assertSame(InvoiceType::Sales, $invoice->type);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame('QT-0001', $invoice->reference);
        $this->assertSame('1080.00', $invoice->total);
        $this->assertSame(QuotationStatus::Invoiced, $quotation->refresh()->status);
        $this->assertSame(LeadStatus::Won, $lead->refresh()->status, 'Invoicing a sent quotation accepts it and wins the lead.');
    }

    public function test_sales_pages_need_the_crm_permission(): void
    {
        $clerk = User::factory()->admin()->for(Role::factory()->with(Permission::InvoicesManage), 'adminRole')->create();

        $this->actingAs($clerk, 'admin')->get(route('admin.leads.index'))->assertForbidden();
        $this->actingAs($clerk, 'admin')->get(route('admin.quotations.index'))->assertForbidden();

        Lead::factory()->create(['title' => 'Office fit-out']);
        $this->actingAs($this->sales, 'admin')->get(route('admin.leads.index'))->assertOk()->assertSee('Office fit-out');
        $this->actingAs($this->sales, 'admin')->get(route('admin.leads.create'))->assertOk();
        $this->actingAs($this->sales, 'admin')->get(route('admin.quotations.index'))->assertOk();
    }
}
