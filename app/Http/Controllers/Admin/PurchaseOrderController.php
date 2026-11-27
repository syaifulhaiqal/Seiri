<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactType;
use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseOrderRequest;
use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Services\Procurement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Purchase orders to suppliers: draft → sent → received into stock, then billed.
 */
class PurchaseOrderController extends Controller
{
    public function __construct(protected Procurement $procurement) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(PurchaseOrderStatus::class)],
        ]);

        return view('admin.purchase-orders.index', [
            'orders' => PurchaseOrder::query()
                ->with(['contact', 'bill'])
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->where('number', 'like', "%{$search}%")
                    ->orWhereHas('contact', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->latest('order_date')
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'openCount' => PurchaseOrder::query()->where('status', PurchaseOrderStatus::Ordered)->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $order = new PurchaseOrder(['order_date' => today()]);
        $fromRequest = PurchaseRequest::query()->with('lines')->find($request->integer('request'));

        if ($fromRequest && ! $fromRequest->canBeOrdered()) {
            $fromRequest = null;
        }

        return view('admin.purchase-orders.form', [
            ...$this->formData($order),
            'fromRequest' => $fromRequest,
            'lines' => $fromRequest?->lineRows() ?? [],
        ]);
    }

    public function store(PurchaseOrderRequest $request): RedirectResponse
    {
        $order = $this->procurement->saveOrder(new PurchaseOrder, $request->validated(), $request->user());

        return to_route('admin.purchase_orders.show', $order)->with('status', __(':number saved as a draft.', ['number' => $order->number]));
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        return view('admin.purchase-orders.show', [
            'order' => $purchaseOrder->load(['contact', 'lines.product', 'purchaseRequest', 'bill', 'creator']),
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        if (! $purchaseOrder->isDraft()) {
            return to_route('admin.purchase_orders.show', $purchaseOrder)->withErrors(['delete' => __('Only drafts can be edited.')]);
        }

        return view('admin.purchase-orders.form', [
            ...$this->formData($purchaseOrder->load('lines')),
            'fromRequest' => $purchaseOrder->purchaseRequest,
            'lines' => $purchaseOrder->lineRows(),
        ]);
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $purchaseOrder->isDraft()) {
            return to_route('admin.purchase_orders.show', $purchaseOrder)->withErrors(['delete' => __('Only drafts can be edited.')]);
        }

        $this->procurement->saveOrder($purchaseOrder, $request->validated(), $request->user());

        return to_route('admin.purchase_orders.show', $purchaseOrder)->with('status', __('Draft updated.'));
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $purchaseOrder->isDraft()) {
            return back()->withErrors(['delete' => __('Only drafts can be deleted. Cancel a sent order instead.')]);
        }

        // Deleting a draft frees its request to be ordered again.
        $this->procurement->cancel($purchaseOrder);
        $number = $purchaseOrder->number;
        $purchaseOrder->delete();

        return to_route('admin.purchase_orders.index')->with('status', __(':number has been deleted.', ['number' => $number]));
    }

    public function place(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        return $this->attempt(fn () => $this->procurement->place($purchaseOrder), __(':number marked as sent to the supplier.', ['number' => $purchaseOrder->number]));
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $date = $request->validate(['date' => ['required', 'date', 'before_or_equal:today']])['date'];

        return $this->attempt(fn () => $this->procurement->receive($purchaseOrder, $date, $request->user()), __(':number received. Its products were added to stock.', ['number' => $purchaseOrder->number]));
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        return $this->attempt(fn () => $this->procurement->cancel($purchaseOrder), __(':number cancelled.', ['number' => $purchaseOrder->number]));
    }

    public function bill(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $bill = $this->procurement->bill($purchaseOrder->load('lines'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['delete' => __($exception->getMessage())]);
        }

        return to_route('admin.bills.edit', $bill)->with('status', __('Draft bill :number made from :order. Check the accounts, then issue it.', ['number' => $bill->number, 'order' => $purchaseOrder->number]));
    }

    /**
     * @param  callable(): void  $action
     */
    protected function attempt(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['delete' => __($exception->getMessage())]);
        }

        return back()->with('status', $success);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(PurchaseOrder $order): array
    {
        return [
            'order' => $order,
            'suppliers' => Contact::query()->whereIn('type', [ContactType::Supplier, ContactType::Both])->orderBy('name')->pluck('name', 'id'),
            'products' => Product::pickerOptions(),
        ];
    }
}
