<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An employee claiming back a work expense, with an optional receipt.
 */
class ClaimSubmitRequest extends FormRequest
{
    protected $errorBag = 'claim';

    public function authorize(): bool
    {
        return (bool) $this->user()?->employee?->isActive();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', Rule::exists(Account::class, 'id')->where('type', AccountType::Expense->value)->where('is_active', true)->whereNull('system_key')],
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . today()->subDays(90)->toDateString()],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:50000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.after_or_equal' => __('Claims must be made within 90 days of the expense.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'account_id' => __('category'),
        ];
    }
}
