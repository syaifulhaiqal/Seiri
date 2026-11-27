<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\InvoiceType;
use App\Enums\Permission;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\StockMovementType;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PurchaseRequestReviewed;
use App\Notifications\PurchaseRequestSubmitted;
use App\Support\DocumentNumber;
use App\Support\Money;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Purchasing: request → approval → purchase order → receive into stock → supplier bill.
 */
class Procurement
{
    public function __construct(protected Inventory $inventory, protected InvoicePosting $posting) {}

    /**
     * Create or update a pending purchase request. A new one asks the approvers.
     *
     * @param  array{needed_by?: string|null, purpose: string, lines: array<int, array<string, mixed>>}  $data
     */
    public function saveRequest(PurchaseRequest $request, array $data, User $by): PurchaseRequest
    {
        if ($request->exists && ! $request->isPending()) {
            throw new InvalidArgumentException('Only pending purchase requests can be edited.');
        }

        $isNew = ! $request->exists;

        DB::transaction(function () use ($request, $data, $by, $isNew): void {
            $request->fill($data);

            if ($isNew) {
                $request->number = DocumentNumber::next(PurchaseRequest::query()->lockForUpdate(), 'PR-');
                $request->status = PurchaseRequestStatus::Pending;
                $request->requester()->associate($by);
            }

            $request->save();
            $request->total = Money::fromCents($request->replaceLines($data['lines']));
            $request->save();
        });

        if ($isNew) {
            Notify::admins(Permission::ProcurementApprove, new PurchaseRequestSubmitted($request));
        }

        return $request;
    }

    public function review(PurchaseRequest $request, User $by, bool $approve, null|string $note): void
    {
        if (! $request->isPending()) {
            throw new InvalidArgumentException('This purchase request has already been decided.');
        }

        $request->status = $approve ? PurchaseRequestStatus::Approved : PurchaseRequestStatus::Rejected;
        $request->review_note = $note;
        $request->reviewer()->associate($by);
        $request->reviewed_at = now();
        $request->save();

        if ($request->requester && $request->requester->isNot($by)) {
            $request->requester->notify(new PurchaseRequestReviewed($request));
        }
    }

    /**
     * Create or update a draft purchase order. An order made from an approved request marks the request as ordered.
     *
     * @param  array{contact_id: int, order_date: string, expected_date?: string|null, notes?: string|null, purchase_request_id?: int|null, lines: array<int, array<string, mixed>>}  $data
     */
    public function saveOrder(PurchaseOrder $order, array $data, User $by): PurchaseOrder
    {
        if ($order->exists && ! $order->isDraft()) {
            throw new InvalidArgumentException('Only draft purchase orders can be edited.');
        }

        return DB::transaction(function () use ($order, $data, $by): PurchaseOrder {
            $order->fill($data);

            if (! $order->exists) {
                $order->number = DocumentNumber::next(PurchaseOrder::query()->lockForUpdate(), 'PO-');
                $order->status = PurchaseOrderStatus::Draft;
                $order->creator()->associate($by);

                if ($request = PurchaseRequest::query()->find($data['purchase_request_id'] ?? null)) {
                    $order->purchaseRequest()->associate($request);
                    $request->status = PurchaseRequestStatus::Ordered;
                    $request->save();
                }
            }

            $order->save();
            $order->total = Money::fromCents($order->replaceLines($data['lines']));
            $order->save();

            return $order;
        });
    }

    /**
     * The order has been sent to the supplier and can no longer be edited.
     */
    public function place(PurchaseOrder $order): void
    {
        if (! $order->isDraft()) {
            throw new InvalidArgumentException('Only draft purchase orders can be sent.');
        }

        $order->status = PurchaseOrderStatus::Ordered;
        $order->save();
    }

    /**
     * The goods arrived: every line linked to a product goes into stock at the order price.
     */
    public function receive(PurchaseOrder $order, string $date, User $by): void
    {
        if (! $order->canBeReceived()) {
            throw new InvalidArgumentException('Only orders sent to the supplier can be received.');
        }

        DB::transaction(function () use ($order, $date, $by): void {
            $order->lines()->with('product')->whereNotNull('product_id')->get()
                ->each(fn (PurchaseOrderLine $line) => $this->inventory->move(
                    $line->product,
                    (float) $line->quantity,
                    StockMovementType::Receipt,
                    $date,
                    $by,
                    __('Received on :number', ['number' => $order->number]),
                    $line->unit_price,
                    $order,
                ));

            $order->status = PurchaseOrderStatus::Received;
            $order->received_at = now();
            $order->save();
        });
    }

    /**
     * Cancel a draft or sent order. Its request can be ordered again.
     */
    public function cancel(PurchaseOrder $order): void
    {
        if (! in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Ordered], true)) {
            throw new InvalidArgumentException('Only draft or sent orders can be cancelled.');
        }

        DB::transaction(function () use ($order): void {
            $order->status = PurchaseOrderStatus::Cancelled;
            $order->save();

            $request = $order->purchaseRequest;

            if ($request && $request->status === PurchaseRequestStatus::Ordered
                && ! $request->purchaseOrders()->whereKeyNot($order->id)->where('status', '!=', PurchaseOrderStatus::Cancelled)->exists()) {
                $request->status = PurchaseRequestStatus::Approved;
                $request->save();
            }
        });
    }

    /**
     * A draft supplier bill with the order's lines, booked to the default expense account (editable before issuing).
     */
    public function bill(PurchaseOrder $order): Invoice
    {
        if (! $order->canBeBilled()) {
            throw new InvalidArgumentException('This order cannot be billed.');
        }

        $accountId = Account::query()->active()->ofType(AccountType::Expense)->orderBy('code')->value('id');

        if ($accountId === null) {
            throw new InvalidArgumentException('Add an expense account first.');
        }

        return DB::transaction(function () use ($order, $accountId): Invoice {
            $bill = $this->posting->saveDraft(new Invoice, InvoiceType::Purchase, [
                'contact_id' => $order->contact_id,
                'issue_date' => today()->toDateString(),
                'due_date' => today()->addDays(config('finance.payment_terms_days'))->toDateString(),
                'reference' => $order->number,
                'notes' => __('From purchase order :number', ['number' => $order->number]),
                'lines' => $order->lines->map(fn (PurchaseOrderLine $line) => [
                    'account_id' => $accountId,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                ])->all(),
            ]);

            $order->bill()->associate($bill);
            $order->save();

            return $bill;
        });
    }
}
