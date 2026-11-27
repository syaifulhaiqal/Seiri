<?php

namespace Tests\Feature\Purchasing;

use App\Enums\ContactType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\Permission;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PurchaseRequestReviewed;
use App\Notifications\PurchaseRequestSubmitted;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProcurementTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $approver;

    protected Product $paper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
        $this->buyer = User::factory()->admin()->for(Role::factory()->with(Permission::ProcurementManage, Permission::InvoicesManage), 'adminRole')->create();
        $this->approver = User::factory()->admin()->for(Role::factory()->with(Permission::ProcurementApprove), 'adminRole')->create();
        $this->paper = Product::factory()->create(['sku' => 'PPR-A4', 'name' => 'A4 paper', 'unit' => 'ream', 'cost_price' => '12.00']);
    }

    public function test_a_request_is_approved_ordered_received_and_billed(): void
    {
        Notification::fake();

        $this->actingAs($this->buyer, 'admin')->get(route('admin.purchase_requests.create'))->assertOk()->assertSee('PPR-A4 · A4 paper');
        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_requests.store'), [
            'purpose' => 'Paper for Q1',
            'lines' => [
                ['product_id' => $this->paper->id, 'description' => 'A4 paper', 'quantity' => 10, 'unit_price' => '12.00'],
                ['product_id' => '', 'description' => 'Courier', 'quantity' => 1, 'unit_price' => '15.00'],
                ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => ''],
            ],
        ])->assertRedirect();

        $request = PurchaseRequest::query()->sole();
        $this->assertSame('PR-0001', $request->number);
        $this->assertSame('135.00', $request->total);
        $this->assertCount(2, $request->lines);
        Notification::assertSentTo($this->approver, PurchaseRequestSubmitted::class);

        // Raising is not approving.
        $this->actingAs($this->buyer, 'admin')->put(route('admin.purchase_requests.review', $request), ['decision' => 'approve'])->assertForbidden();

        $this->actingAs($this->approver, 'admin')->get(route('admin.purchase_requests.show', $request))->assertOk()->assertSee('Paper for Q1');
        $this->actingAs($this->approver, 'admin')->put(route('admin.purchase_requests.review', $request), ['decision' => 'approve'])->assertRedirect();
        $this->assertSame(PurchaseRequestStatus::Approved, $request->refresh()->status);
        Notification::assertSentTo($this->buyer, PurchaseRequestReviewed::class);

        $supplier = Contact::factory()->create(['type' => ContactType::Supplier, 'name' => 'Kedai Kertas Sdn Bhd']);
        $this->actingAs($this->buyer, 'admin')->get(route('admin.purchase_orders.create', ['request' => $request->id]))->assertOk()->assertSee('Paper for Q1');
        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_orders.store'), [
            'contact_id' => $supplier->id,
            'order_date' => today()->toDateString(),
            'purchase_request_id' => $request->id,
            'lines' => [['product_id' => $this->paper->id, 'description' => 'A4 paper', 'quantity' => 10, 'unit_price' => '11.50']],
        ])->assertRedirect();

        $order = PurchaseOrder::query()->sole();
        $this->assertSame('PO-0001', $order->number);
        $this->assertSame(PurchaseRequestStatus::Ordered, $request->refresh()->status);

        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_orders.place', $order))->assertRedirect();
        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_orders.receive', $order), ['date' => today()->toDateString()])->assertRedirect();

        $this->assertSame(PurchaseOrderStatus::Received, $order->refresh()->status);
        $this->assertSame(10.0, $this->paper->onHand());
        $this->actingAs($this->buyer, 'admin')->get(route('admin.purchase_orders.show', $order))->assertOk()->assertSee('Kedai Kertas Sdn Bhd');

        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_orders.bill', $order))->assertRedirect();

        $bill = Invoice::query()->sole();
        $this->assertSame(InvoiceType::Purchase, $bill->type);
        $this->assertSame(InvoiceStatus::Draft, $bill->status);
        $this->assertSame('PO-0001', $bill->reference);
        $this->assertSame('115.00', $bill->total);
        $this->assertSame($bill->id, $order->refresh()->bill_id);

        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_orders.bill', $order))->assertSessionHasErrors('delete');
        $this->assertSame(1, Invoice::query()->count());
    }

    public function test_cancelling_an_order_frees_its_request(): void
    {
        $request = PurchaseRequest::factory()->approved()->create();
        $supplier = Contact::factory()->create(['type' => ContactType::Supplier]);

        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_orders.store'), [
            'contact_id' => $supplier->id,
            'order_date' => today()->toDateString(),
            'purchase_request_id' => $request->id,
            'lines' => [['description' => 'Chairs', 'quantity' => 2, 'unit_price' => '300']],
        ]);
        $order = PurchaseOrder::query()->sole();
        $this->actingAs($this->buyer, 'admin')->get(route('admin.purchase_orders.edit', $order))->assertOk()->assertSee('Chairs');
        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_orders.place', $order));
        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_orders.cancel', $order))->assertRedirect();

        $this->assertSame(PurchaseOrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame(PurchaseRequestStatus::Approved, $request->refresh()->status);
        $this->actingAs($this->buyer, 'admin')->post(route('admin.purchase_orders.receive', $order), ['date' => today()->toDateString()])->assertSessionHasErrors('delete');
    }

    public function test_stock_can_be_adjusted_but_not_below_zero(): void
    {
        $storekeeper = User::factory()->admin()->for(Role::factory()->with(Permission::InventoryManage), 'adminRole')->create();

        $this->actingAs($storekeeper, 'admin')->post(route('admin.products.adjust', $this->paper), [
            'direction' => 'in', 'quantity' => 5, 'date' => today()->toDateString(), 'note' => 'Stock count',
        ])->assertRedirect();
        $this->actingAs($storekeeper, 'admin')->post(route('admin.products.adjust', $this->paper), [
            'direction' => 'out', 'quantity' => 6, 'date' => today()->toDateString(), 'note' => 'Issued to Finance',
        ])->assertSessionHasErrorsIn('stock', 'quantity');
        $this->actingAs($storekeeper, 'admin')->post(route('admin.products.adjust', $this->paper), [
            'direction' => 'out', 'quantity' => 2, 'date' => today()->toDateString(), 'note' => 'Issued to Finance',
        ])->assertRedirect();

        $this->assertSame(3.0, $this->paper->onHand());
        $this->actingAs($storekeeper, 'admin')->get(route('admin.products.index'))->assertOk()->assertSee('A4 paper')->assertSee('RM 36.00');
        $this->actingAs($storekeeper, 'admin')->get(route('admin.products.show', $this->paper))->assertOk()->assertSee('Issued to Finance');
        $this->actingAs($storekeeper, 'admin')->get(route('admin.products.edit', $this->paper))->assertOk();

        // A product with stock history is kept.
        $this->actingAs($storekeeper, 'admin')->delete(route('admin.products.destroy', $this->paper))->assertSessionHasErrors('delete');
        $this->actingAs($storekeeper, 'admin')->get(route('admin.purchase_orders.index'))->assertForbidden();
    }

    public function test_a_product_is_added_with_its_sku_in_capitals(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create(), 'admin')->post(route('admin.products.store'), [
            'sku' => 'tnr-01', 'name' => 'Toner', 'unit' => 'pcs', 'cost_price' => '80', 'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame('TNR-01', Product::query()->where('name', 'Toner')->sole()->sku);
        $this->actingAs($this->buyer, 'admin')->get(route('admin.purchase_requests.index'))->assertOk();
        $this->actingAs($this->buyer, 'admin')->get(route('admin.purchase_orders.index'))->assertOk();
    }
}
