<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContactType;
use App\Enums\PurchaseRequestStatus;
use App\Http\Requests\Concerns\ValidatesLineItems;
use App\Models\Contact;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A draft purchase order to a supplier, optionally from an approved purchase request.
 */
class PurchaseOrderRequest extends FormRequest
{
    use ValidatesLineItems;

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'contact_id' => ['required', Rule::exists(Contact::class, 'id')->whereIn('type', [ContactType::Supplier->value, ContactType::Both->value])],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // Only when creating: an order cannot be moved to another request later.
            'purchase_request_id' => [
                Rule::excludeIf($this->route('purchase_order') !== null),
                'nullable',
                Rule::exists(PurchaseRequest::class, 'id')->where('status', PurchaseRequestStatus::Approved->value),
            ],
            ...$this->lineRules(),
        ];
    }
}
