<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Recording money received for an invoice or paid for a bill.
 */
class PaymentRequest extends FormRequest
{
    protected $errorBag = 'payment';

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
            'account_id' => ['required', Rule::exists(Account::class, 'id')],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'account_id' => __('account'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Invoice $invoice */
                $invoice = $this->route('invoice');

                if ($validator->errors()->has('amount')) {
                    return;
                }

                if (Money::toCents($this->input('amount')) > $invoice->balanceDueCents()) {
                    $validator->errors()->add('amount', __('The balance due is :amount.', ['amount' => Money::format(Money::fromCents($invoice->balanceDueCents()))]));
                }

                if (! Account::query()->cashAndBank()->whereKey($this->input('account_id'))->exists()) {
                    $validator->errors()->add('account_id', __('Choose a bank or cash account.'));
                }
            },
        ];
    }
}
