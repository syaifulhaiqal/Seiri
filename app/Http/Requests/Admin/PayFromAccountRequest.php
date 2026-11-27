<?php

namespace App\Http\Requests\Admin;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Paying something out of a bank or cash account: salaries or an expense claim.
 */
class PayFromAccountRequest extends FormRequest
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
                if (! Account::query()->cashAndBank()->whereKey($this->input('account_id'))->exists()) {
                    $validator->errors()->add('account_id', __('Choose a bank or cash account.'));
                }
            },
        ];
    }
}
