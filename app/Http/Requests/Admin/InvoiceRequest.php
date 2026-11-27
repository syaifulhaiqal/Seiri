<?php

namespace App\Http\Requests\Admin;

use App\Enums\InvoiceType;
use App\Models\Account;
use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A draft invoice or bill. The controller passes the document type through the route.
 */
class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lines' => collect($this->input('lines', []))
                ->reject(fn ($line) => blank($line['description'] ?? null) && blank($line['unit_price'] ?? null))
                ->values()
                ->all(),
        ]);
    }

    public function documentType(): InvoiceType
    {
        return $this->routeIs('admin.bills.*') ? InvoiceType::Purchase : InvoiceType::Sales;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $type = $this->documentType();

        return [
            'contact_id' => ['required', Rule::exists(Contact::class, 'id')->whereIn('type', array_map(fn ($case) => $case->value, $type->contactTypes()))],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.account_id' => ['required', Rule::exists(Account::class, 'id')->where('type', $type->lineAccountType()->value)->where('is_active', true)],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => __('Add at least one line.'),
            'lines.*.description.required' => __('Describe every line.'),
            'lines.*.account_id.required' => __('Choose an account for every line.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_id' => strtolower($this->documentType()->contactLabel()),
            'lines.*.quantity' => __('quantity'),
            'lines.*.unit_price' => __('price'),
        ];
    }
}
