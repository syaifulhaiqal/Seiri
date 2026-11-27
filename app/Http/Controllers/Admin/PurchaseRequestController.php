<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveReviewRequest;
use App\Http\Requests\Admin\PurchaseRequestRequest;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Services\Procurement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Purchase requests: raised by purchasing, approved by whoever holds procurement.approve.
 */
class PurchaseRequestController extends Controller
{
    public function __construct(protected Procurement $procurement) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(PurchaseRequestStatus::class)],
        ]);

        return view('admin.purchase-requests.index', [
            'requests' => PurchaseRequest::query()
                ->with('requester')
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->where('number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")))
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->orderByRaw('case when status = ? then 0 else 1 end', [PurchaseRequestStatus::Pending->value])
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'pendingCount' => PurchaseRequest::query()->where('status', PurchaseRequestStatus::Pending)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.purchase-requests.form', [
            'purchaseRequest' => new PurchaseRequest,
            'products' => Product::pickerOptions(),
        ]);
    }

    public function store(PurchaseRequestRequest $request): RedirectResponse
    {
        $purchaseRequest = $this->procurement->saveRequest(new PurchaseRequest, $request->validated(), $request->user());

        return to_route('admin.purchase_requests.show', $purchaseRequest)->with('status', __(':number sent for approval.', ['number' => $purchaseRequest->number]));
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        return view('admin.purchase-requests.show', [
            'purchaseRequest' => $purchaseRequest->load(['lines.product', 'requester', 'reviewer', 'purchaseOrders.contact']),
        ]);
    }

    public function edit(PurchaseRequest $purchaseRequest): View|RedirectResponse
    {
        if (! $purchaseRequest->isPending()) {
            return to_route('admin.purchase_requests.show', $purchaseRequest)->withErrors(['delete' => __('Only requests waiting for approval can be edited.')]);
        }

        return view('admin.purchase-requests.form', [
            'purchaseRequest' => $purchaseRequest->load('lines'),
            'products' => Product::pickerOptions(),
        ]);
    }

    public function update(PurchaseRequestRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        if (! $purchaseRequest->isPending()) {
            return to_route('admin.purchase_requests.show', $purchaseRequest)->withErrors(['delete' => __('Only requests waiting for approval can be edited.')]);
        }

        $this->procurement->saveRequest($purchaseRequest, $request->validated(), $request->user());

        return to_route('admin.purchase_requests.show', $purchaseRequest)->with('status', __('Purchase request updated.'));
    }

    public function review(LeaveReviewRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        Gate::authorize('procurement.approve');

        if (! $purchaseRequest->isPending()) {
            return back()->withErrors(['review' => __('This request has already been :status.', ['status' => strtolower($purchaseRequest->status->label())])]);
        }

        $approve = $request->validated('decision') === 'approve';
        $this->procurement->review($purchaseRequest, $request->user(), $approve, $request->validated('review_note'));

        return to_route('admin.purchase_requests.show', $purchaseRequest)->with('status', $approve
            ? __(':number approved.', ['number' => $purchaseRequest->number])
            : __(':number rejected.', ['number' => $purchaseRequest->number]));
    }

    public function cancel(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        if (! in_array($purchaseRequest->status, [PurchaseRequestStatus::Pending, PurchaseRequestStatus::Approved], true)) {
            return back()->withErrors(['delete' => __('Only requests that have not been ordered can be cancelled.')]);
        }

        $purchaseRequest->status = PurchaseRequestStatus::Cancelled;
        $purchaseRequest->save();

        return to_route('admin.purchase_requests.show', $purchaseRequest)->with('status', __(':number cancelled.', ['number' => $purchaseRequest->number]));
    }
}
