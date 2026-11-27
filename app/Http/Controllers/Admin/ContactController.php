<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactType;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(ContactType::class)],
        ]);

        $contacts = Contact::query()
            ->search($filters['search'] ?? null)
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->withCount('invoices')
            ->withSum(['invoices as outstanding' => fn ($query) => $query->whereIn('status', InvoiceStatus::outstanding())], 'total')
            ->withSum(['invoices as outstanding_paid' => fn ($query) => $query->whereIn('status', InvoiceStatus::outstanding())], 'amount_paid')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.finance.contacts.index', [
            'contacts' => $contacts,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.finance.contacts.form', [
            'contact' => new Contact(['type' => ContactType::tryFrom((string) $request->query('type')) ?? ContactType::Customer]),
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $contact = Contact::query()->create($request->validated());

        return to_route('admin.contacts.index')->with('status', __(':name has been added.', ['name' => $contact->name]));
    }

    public function edit(Contact $contact): View
    {
        return view('admin.finance.contacts.form', [
            'contact' => $contact,
        ]);
    }

    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->validated());

        return to_route('admin.contacts.index')->with('status', __('Contact updated.'));
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        if ($contact->invoices()->exists()) {
            return back()->withErrors(['delete' => __(':name has invoices or bills, so it cannot be deleted.', ['name' => $contact->name])]);
        }

        $name = $contact->name;
        $contact->delete();

        return to_route('admin.contacts.index')->with('status', __(':name has been deleted.', ['name' => $name]));
    }
}
