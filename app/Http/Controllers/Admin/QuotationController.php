<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactType;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuotationRequest;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\Sales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Quotations to customers. An accepted quotation becomes a draft sales invoice.
 */
class QuotationController extends Controller
{
    public function __construct(protected Sales $sales) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(QuotationStatus::class)],
        ]);

        return view('admin.quotations.index', [
            'quotations' => Quotation::query()
                ->with(['contact', 'invoice'])
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->where('number', 'like', "%{$search}%")
                    ->orWhereHas('contact', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->latest('issue_date')
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'openValue' => (string) Quotation::query()->where('status', QuotationStatus::Sent)->sum('total'),
        ]);
    }

    public function create(Request $request): View
    {
        $lead = Lead::query()->find($request->integer('lead'));

        $quotation = new Quotation([
            'contact_id' => $lead?->contact_id ?? ($request->integer('contact') ?: null),
            'issue_date' => today(),
            'valid_until' => today()->addDays(30),
            'tax_rate' => config('finance.default_tax_rate'),
        ]);

        return view('admin.quotations.form', [...$this->formData($quotation), 'lead' => $lead, 'lines' => []]);
    }

    public function store(QuotationRequest $request): RedirectResponse
    {
        $quotation = $this->sales->saveQuotation(new Quotation, $request->validated(), $request->user());

        return to_route('admin.quotations.show', $quotation)->with('status', __(':number saved as a draft.', ['number' => $quotation->number]));
    }

    public function show(Quotation $quotation): View
    {
        return view('admin.quotations.show', [
            'quotation' => $quotation->load(['contact', 'lines.product', 'lead', 'invoice', 'creator']),
        ]);
    }

    public function edit(Quotation $quotation): View|RedirectResponse
    {
        if (! $quotation->isDraft()) {
            return to_route('admin.quotations.show', $quotation)->withErrors(['delete' => __('Only drafts can be edited.')]);
        }

        return view('admin.quotations.form', [...$this->formData($quotation->load('lines')), 'lead' => $quotation->lead, 'lines' => $quotation->lineRows()]);
    }

    public function update(QuotationRequest $request, Quotation $quotation): RedirectResponse
    {
        if (! $quotation->isDraft()) {
            return to_route('admin.quotations.show', $quotation)->withErrors(['delete' => __('Only drafts can be edited.')]);
        }

        $this->sales->saveQuotation($quotation, $request->validated(), $request->user());

        return to_route('admin.quotations.show', $quotation)->with('status', __('Draft updated.'));
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        if (! $quotation->isDraft()) {
            return back()->withErrors(['delete' => __('Only drafts can be deleted.')]);
        }

        $number = $quotation->number;
        $quotation->delete();

        return to_route('admin.quotations.index')->with('status', __(':number has been deleted.', ['number' => $number]));
    }

    public function mark(Request $request, Quotation $quotation): RedirectResponse
    {
        $status = QuotationStatus::from($request->validate(['status' => ['required', Rule::in(['sent', 'accepted', 'declined'])]])['status']);

        try {
            $this->sales->mark($quotation, $status);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['delete' => __($exception->getMessage())]);
        }

        return back()->with('status', __(':number marked as :status.', ['number' => $quotation->number, 'status' => strtolower($status->label())]));
    }

    public function invoice(Quotation $quotation): RedirectResponse
    {
        try {
            $invoice = $this->sales->invoice($quotation->load(['lines', 'lead']));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['delete' => __($exception->getMessage())]);
        }

        return to_route('admin.invoices.edit', $invoice)->with('status', __('Draft invoice :number made from :quotation. Check it, then issue it.', ['number' => $invoice->number, 'quotation' => $quotation->number]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Quotation $quotation): array
    {
        return [
            'quotation' => $quotation,
            'customers' => Contact::query()->whereIn('type', [ContactType::Customer, ContactType::Both])->orderBy('name')->pluck('name', 'id'),
            'products' => Product::pickerOptions(),
        ];
    }
}
