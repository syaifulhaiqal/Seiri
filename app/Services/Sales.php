<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\ContactType;
use App\Enums\InvoiceType;
use App\Enums\LeadStatus;
use App\Enums\QuotationStatus;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\User;
use App\Support\DocumentNumber;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Leads becoming customers, and quotations: draft → sent → accepted or declined → invoiced.
 */
class Sales
{
    public function __construct(protected InvoicePosting $posting) {}

    /**
     * Create the lead's customer record (or reuse the one with the same email) and link it.
     */
    public function convert(Lead $lead): Contact
    {
        if ($lead->contact) {
            return $lead->contact;
        }

        return DB::transaction(function () use ($lead): Contact {
            $contact = ($lead->email ? Contact::query()->where('email', $lead->email)->first() : null) ?? new Contact;

            if (! $contact->exists) {
                $contact->fill([
                    'name' => $lead->company ?: $lead->contact_name,
                    'email' => $lead->email,
                    'phone_number' => $lead->phone_number,
                ]);
                $contact->type = ContactType::Customer;
                $contact->save();
            } elseif ($contact->type === ContactType::Supplier) {
                $contact->type = ContactType::Both;
                $contact->save();
            }

            $lead->contact()->associate($contact);
            $lead->save();

            return $contact;
        });
    }

    /**
     * Create or update a draft quotation.
     *
     * @param  array{contact_id: int, issue_date: string, valid_until: string, tax_rate?: float|string|null, notes?: string|null, lead_id?: int|null, lines: array<int, array<string, mixed>>}  $data
     */
    public function saveQuotation(Quotation $quotation, array $data, User $by): Quotation
    {
        if ($quotation->exists && ! $quotation->isDraft()) {
            throw new InvalidArgumentException('Only draft quotations can be edited.');
        }

        return DB::transaction(function () use ($quotation, $data, $by): Quotation {
            $quotation->fill([...$data, 'tax_rate' => (float) ($data['tax_rate'] ?? 0)]);

            if (! $quotation->exists) {
                $quotation->number = DocumentNumber::next(Quotation::query()->lockForUpdate(), 'QT-');
                $quotation->status = QuotationStatus::Draft;
                $quotation->creator()->associate($by);
                $quotation->lead()->associate($data['lead_id'] ?? null);
            }

            $quotation->save();

            $subtotal = $quotation->replaceLines($data['lines']);
            $tax = (int) round($subtotal * (float) $quotation->tax_rate / 100);

            $quotation->subtotal = Money::fromCents($subtotal);
            $quotation->tax_amount = Money::fromCents($tax);
            $quotation->total = Money::fromCents($subtotal + $tax);
            $quotation->save();

            return $quotation;
        });
    }

    /**
     * Move a quotation to its next status. Accepting one marks its lead as won.
     */
    public function mark(Quotation $quotation, QuotationStatus $status): void
    {
        $allowed = match ($status) {
            QuotationStatus::Sent => $quotation->status === QuotationStatus::Draft,
            QuotationStatus::Accepted, QuotationStatus::Declined => $quotation->status === QuotationStatus::Sent,
            default => false,
        };

        if (! $allowed) {
            throw new InvalidArgumentException('This quotation cannot be changed that way.');
        }

        DB::transaction(function () use ($quotation, $status): void {
            $quotation->status = $status;
            $quotation->save();

            if ($status === QuotationStatus::Accepted && $quotation->lead && $quotation->lead->status->isOpen()) {
                $quotation->lead->status = LeadStatus::Won;
                $quotation->lead->save();
            }
        });
    }

    /**
     * A draft sales invoice with the quotation's lines and tax, booked to the sales account (editable before issuing).
     */
    public function invoice(Quotation $quotation): Invoice
    {
        if (! $quotation->canBeInvoiced()) {
            throw new InvalidArgumentException('This quotation cannot be invoiced.');
        }

        $accountId = Account::query()->where('system_key', 'sales')->value('id')
            ?? Account::query()->active()->ofType(AccountType::Income)->orderBy('code')->value('id');

        if ($accountId === null) {
            throw new InvalidArgumentException('Add an income account first.');
        }

        return DB::transaction(function () use ($quotation, $accountId): Invoice {
            $invoice = $this->posting->saveDraft(new Invoice, InvoiceType::Sales, [
                'contact_id' => $quotation->contact_id,
                'issue_date' => today()->toDateString(),
                'due_date' => today()->addDays(config('finance.payment_terms_days'))->toDateString(),
                'reference' => $quotation->number,
                'tax_rate' => $quotation->tax_rate,
                'notes' => $quotation->notes,
                'lines' => $quotation->lines->map(fn (QuotationLine $line) => [
                    'account_id' => $accountId,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                ])->all(),
            ]);

            if ($quotation->status === QuotationStatus::Sent) {
                $this->mark($quotation, QuotationStatus::Accepted);
            }

            $quotation->invoice()->associate($invoice);
            $quotation->status = QuotationStatus::Invoiced;
            $quotation->save();

            return $invoice;
        });
    }
}
