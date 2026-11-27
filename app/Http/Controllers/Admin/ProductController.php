<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Requests\Admin\StockAdjustmentRequest;
use App\Models\Product;
use App\Services\Inventory;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Products and their stock. Stock comes in from received purchase orders and goes out by adjustment.
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'stock' => ['nullable', 'in:low,inactive'],
        ]);

        $products = Product::query()
            ->withOnHand()
            ->search($filters['search'] ?? null)
            ->when(($filters['stock'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false), fn ($query) => $query->active())
            ->orderBy('name')
            ->get();

        if (($filters['stock'] ?? null) === 'low') {
            $products = $products->filter->isLow()->values();
        }

        $active = Product::query()->active()->withOnHand()->get();

        return view('admin.products.index', [
            'products' => $products,
            'filters' => $filters,
            'totals' => [
                'count' => $active->count(),
                'value' => Money::fromCents($active->sum(fn (Product $product) => max(0, $product->stockValueCents()))),
                'low' => $active->filter->isLow()->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::query()->create($request->validated());

        return to_route('admin.products.show', $product)->with('status', __(':name has been added.', ['name' => $product->name]));
    }

    public function show(Product $product): View
    {
        return view('admin.products.show', [
            'product' => $product,
            'movements' => $product->stockMovements()->with(['creator', 'source'])->latest('date')->latest('id')->paginate(20),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', ['product' => $product]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return to_route('admin.products.show', $product)->with('status', __('Product updated.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->stockMovements()->exists()) {
            return back()->withErrors(['delete' => __('This product has stock history. Mark it inactive instead.')]);
        }

        $name = $product->name;
        $product->delete();

        return to_route('admin.products.index')->with('status', __(':name has been deleted.', ['name' => $name]));
    }

    public function adjust(StockAdjustmentRequest $request, Product $product, Inventory $inventory): RedirectResponse
    {
        $quantity = $request->signedQuantity();

        $inventory->move(
            $product,
            $quantity,
            $quantity < 0 ? StockMovementType::Issue : StockMovementType::Adjustment,
            $request->validated('date'),
            $request->user(),
            $request->validated('note'),
            $product->cost_price,
        );

        return to_route('admin.products.show', $product)->with('status', __('Stock updated. :count :unit on hand.', ['count' => $product->onHand(), 'unit' => $product->unit]));
    }
}
