<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContactType;
use App\Http\Requests\Concerns\ValidatesLineItems;
use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A draft quotation to a customer, optionally for a lead.
 */
class QuotationRequest extends FormRequest
{
    use ValidatesLineItems {
        attributes as lineAttributes;
    }

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
            'contact_id' => ['required', Rule::exists(Contact::class, 'id')->whereIn('type', [ContactType::Customer->value, ContactType::Both->value])],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issue_date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lead_id' => [Rule::excludeIf($this->route('quotation') !== null), 'nullable', Rule::exists(Lead::class, 'id')],
            ...$this->lineRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [...$this->lineAttributes(), 'contact_id' => __('customer')];
    }
}
