<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\DocumentNumber;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Drafting, issuing, paying and voiding invoices and bills, with the matching journal entries.
 */
class InvoicePosting
{
    public function __construct(protected Ledger $ledger) {}

    /**
     * Create or update a draft from validated form data.
     *
     * @param  array{contact_id: int, issue_date: string, due_date: string, reference?: string|null, tax_rate?: float|string|null, notes?: string|null, lines: array<int, array{account_id: int, description: string, quantity: float|string, unit_price: float|string}>}  $data
     */
    public function saveDraft(Invoice $invoice, InvoiceType $type, array $data): Invoice
    {
        if ($invoice->exists && ! $invoice->isDraft()) {
            throw new InvalidArgumentException('Only drafts can be edited.');
        }

        return DB::transaction(function () use ($invoice, $type, $data): Invoice {
            $invoice->fill([
                'contact_id' => $data['contact_id'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'reference' => $data['reference'] ?? null,
                'tax_rate' => $type === InvoiceType::Sales ? (float) ($data['tax_rate'] ?? 0) : 0,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! $invoice->exists) {
                $invoice->type = $type;
                $invoice->status = InvoiceStatus::Draft;
                $invoice->number = DocumentNumber::next(Invoice::query()->ofType($type)->lockForUpdate(), $type->numberPrefix());
                $invoice->created_by = auth()->id();
            }

            $lines = collect($data['lines'])->values()->map(fn (array $line, int $index) => [
                'account_id' => $line['account_id'],
                'description' => $line['description'],
                'quantity' => round((float) $line['quantity'], 2),
                'unit_price' => round((float) $line['unit_price'], 2),
                'amount' => Money::fromCents((int) round(Money::toCents($line['unit_price']) * (float) $line['quantity'])),
                'sort' => $index,
            ]);

            $subtotal = $lines->sum(fn (array $line) => Money::toCents($line['amount']));
            $tax = (int) round($subtotal * (float) $invoice->tax_rate / 100);

            $invoice->subtotal = Money::fromCents($subtotal);
            $invoice->tax_amount = Money::fromCents($tax);
            $invoice->total = Money::fromCents($subtotal + $tax);
            $invoice->save();

            $invoice->lines()->delete();
            $invoice->lines()->createMany($lines->all());

            return $invoice;
        });
    }

    /**
     * Finalise a draft: it becomes payable and is posted to receivables or payables.
     */
    public function issue(Invoice $invoice): void
    {
        if (! $invoice->isDraft()) {
            throw new InvalidArgumentException('Only drafts can be issued.');
        }

        $invoice->loadMissing(['lines.account', 'contact']);
        $total = Money::toCents($invoice->total);

        if ($total <= 0) {
            throw new InvalidArgumentException('An invoice needs a total above zero.');
        }

        DB::transaction(function () use ($invoice, $total): void {
            $sales = $invoice->type === InvoiceType::Sales;
            $lines = $invoice->lines->map(fn ($line) => [
                'account' => $line->account,
                $sales ? 'credit' : 'debit' => Money::toCents($line->amount),
                'description' => $line->description,
            ])->all();

            if ($sales) {
                $lines[] = ['account' => 'sales_tax', 'credit' => Money::toCents($invoice->tax_amount)];
                $lines[] = ['account' => 'accounts_receivable', 'debit' => $total];
            } else {
                $lines[] = ['account' => 'accounts_payable', 'credit' => $total];
            }

            $entry = $this->ledger->post(
                $invoice->issue_date,
                "{$invoice->type->label()} {$invoice->number} · {$invoice->contact->name}",
                $lines,
                $invoice,
                $invoice->reference ?: $invoice->number,
            );

            $invoice->status = InvoiceStatus::Open;
            $invoice->issued_at = now();
            $invoice->journalEntry()->associate($entry);
            $invoice->save();
        });
    }

    public function recordPayment(Invoice $invoice, Account $account, Carbon|string $date, int $amountCents, null|PaymentMethod $method = null, null|string $reference = null): Payment
    {
        if (! $invoice->isOutstanding()) {
            throw new InvalidArgumentException('Only issued, unpaid documents can take a payment.');
        }

        if ($amountCents <= 0 || $amountCents > $invoice->balanceDueCents()) {
            throw new InvalidArgumentException('The payment must be above zero and no more than the balance due.');
        }

        return DB::transaction(function () use ($invoice, $account, $date, $amountCents, $method, $reference): Payment {
            $sales = $invoice->type === InvoiceType::Sales;

            $payment = new Payment([
                'account_id' => $account->id,
                'date' => Carbon::parse($date)->toDateString(),
                'amount' => Money::fromCents($amountCents),
                'method' => $method,
                'reference' => $reference,
            ]);
            $payment->invoice()->associate($invoice);
            $payment->created_by = auth()->id();
            $payment->save();

            $entry = $this->ledger->post($date, ($sales ? __('Payment received') : __('Payment made')) . " · {$invoice->number}", [
                ['account' => $account, $sales ? 'debit' : 'credit' => $amountCents],
                ['account' => $sales ? 'accounts_receivable' : 'accounts_payable', $sales ? 'credit' : 'debit' => $amountCents],
            ], $payment, $reference ?: $invoice->number);

            $payment->journalEntry()->associate($entry);
            $payment->saveQuietly();

            $invoice->amount_paid = Money::fromCents(Money::toCents($invoice->amount_paid) + $amountCents);
            $invoice->status = $invoice->balanceDueCents() === 0 ? InvoiceStatus::Paid : InvoiceStatus::PartiallyPaid;
            $invoice->save();

            return $payment;
        });
    }

    /**
     * Cancel a document. Issued ones are reversed in the ledger; paid ones must be refunded first.
     */
    public function void(Invoice $invoice, null|string $reason = null): void
    {
        if ($invoice->status === InvoiceStatus::Void || Money::toCents($invoice->amount_paid) > 0) {
            throw new InvalidArgumentException('Only drafts and unpaid documents can be voided.');
        }

        DB::transaction(function () use ($invoice, $reason): void {
            if ($invoice->journalEntry) {
                $this->ledger->reverse($invoice->journalEntry, today(), __('Void :number', ['number' => $invoice->number]) . ($reason ? " · {$reason}" : ''), $invoice);
            }

            $invoice->status = InvoiceStatus::Void;
            $invoice->voided_at = now();
            $invoice->save();
            $invoice->audit(AuditEvent::Voided, $reason);
        });
    }
}
