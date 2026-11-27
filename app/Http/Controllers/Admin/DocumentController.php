<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Http\Requests\Admin\PaymentRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Services\InvoicePosting;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Shared screens for sales invoices (InvoiceController) and supplier bills (BillController).
 */
abstract class DocumentController extends Controller
{
    abstract protected function type(): InvoiceType;

    public function __construct(protected InvoicePosting $posting) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([...array_column(InvoiceStatus::cases(), 'value'), 'overdue'])],
        ]);

        $documents = Invoice::query()
            ->ofType($this->type())
            ->with('contact')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $status === 'overdue'
                ? $query->overdue()
                : $query->where('status', $status))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $outstanding = Invoice::query()->ofType($this->type())->outstanding();

        return view('admin.finance.documents.index', [
            'type' => $this->type(),
            'documents' => $documents,
            'filters' => $filters,
            'totals' => [
                'outstanding' => (float) (clone $outstanding)->sum('total') - (float) (clone $outstanding)->sum('amount_paid'),
                'overdue' => (float) (clone $outstanding)->overdue()->sum('total') - (float) (clone $outstanding)->overdue()->sum('amount_paid'),
                'drafts' => Invoice::query()->ofType($this->type())->where('status', InvoiceStatus::Draft)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $invoice = new Invoice([
            'contact_id' => $request->integer('contact') ?: null,
            'issue_date' => today(),
            'due_date' => today()->addDays(config('finance.payment_terms_days')),
            'tax_rate' => $this->type() === InvoiceType::Sales ? config('finance.default_tax_rate') : 0,
        ]);
        $invoice->type = $this->type();

        return view('admin.finance.documents.form', $this->formData($invoice));
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->posting->saveDraft(new Invoice, $this->type(), $request->validated());

        return to_route($this->type()->route() . '.show', $invoice)->with('status', __(':number saved as a draft.', ['number' => $invoice->number]));
    }

    public function show(Invoice $invoice): View
    {
        $this->ensureType($invoice);
        $invoice->load(['contact', 'lines.account', 'payments.account', 'journalEntry']);

        return view('admin.finance.documents.show', [
            'type' => $this->type(),
            'invoice' => $invoice,
            'bankAccounts' => Account::query()->cashAndBank()->active()->orderBy('code')->get()->mapWithKeys(fn (Account $account) => [$account->id => $account->displayName()]),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        $this->ensureType($invoice);

        if (! $invoice->isDraft()) {
            return to_route($this->type()->route() . '.show', $invoice)->withErrors(['delete' => __('Only drafts can be edited. Void it and create a new one instead.')]);
        }

        return view('admin.finance.documents.form', $this->formData($invoice->load('lines')));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureType($invoice);

        if (! $invoice->isDraft()) {
            return to_route($this->type()->route() . '.show', $invoice)->withErrors(['delete' => __('Only drafts can be edited.')]);
        }

        $this->posting->saveDraft($invoice, $this->type(), $request->validated());

        return to_route($this->type()->route() . '.show', $invoice)->with('status', __('Draft updated.'));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->ensureType($invoice);

        if (! $invoice->isDraft()) {
            return back()->withErrors(['delete' => __('Only drafts can be deleted. Void an issued document instead.')]);
        }

        $number = $invoice->number;
        $invoice->delete();

        return to_route($this->type()->route() . '.index')->with('status', __(':number has been deleted.', ['number' => $number]));
    }

    public function issue(Invoice $invoice): RedirectResponse
    {
        $this->ensureType($invoice);

        if (! $invoice->isDraft() || Money::toCents($invoice->total) <= 0) {
            return back()->withErrors(['delete' => __('Only drafts with a total above zero can be issued.')]);
        }

        $this->posting->issue($invoice);

        return back()->with('status', __(':number issued and posted to the ledger.', ['number' => $invoice->number]));
    }

    public function pay(PaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureType($invoice);

        if (! $invoice->isOutstanding()) {
            return back()->withErrors(['delete' => __('This document is not awaiting payment.')]);
        }

        $data = $request->validated();

        $this->posting->recordPayment(
            $invoice,
            Account::query()->findOrFail($data['account_id']),
            $data['date'],
            Money::toCents($data['amount']),
            PaymentMethod::tryFrom((string) ($data['method'] ?? '')),
            $data['reference'] ?? null,
        );

        return back()->with('status', __('Payment of :amount recorded.', ['amount' => Money::format($data['amount'])]));
    }

    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureType($invoice);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null;

        if ($invoice->status === InvoiceStatus::Void || Money::toCents($invoice->amount_paid) > 0) {
            return back()->withErrors(['delete' => __('Only drafts and unpaid documents can be voided.')]);
        }

        $this->posting->void($invoice, $reason);

        return back()->with('status', __(':number voided.', ['number' => $invoice->number]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Invoice $invoice): array
    {
        return [
            'type' => $this->type(),
            'invoice' => $invoice,
            'contacts' => Contact::query()->usableFor($this->type())->orderBy('name')->pluck('name', 'id'),
            'lineAccounts' => Account::query()->active()->ofType($this->type()->lineAccountType())->orderBy('code')->get()
                ->mapWithKeys(fn (Account $account) => [$account->id => $account->displayName()]),
            'defaultAccountId' => Account::query()->active()->ofType($this->type()->lineAccountType())
                ->orderByRaw('case when system_key = ? then 0 else 1 end', ['sales'])->orderBy('code')->value('id'),
        ];
    }

    protected function ensureType(Invoice $invoice): void
    {
        abort_unless($invoice->type === $this->type(), 404);
    }
}
