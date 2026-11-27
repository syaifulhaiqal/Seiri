<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create and update an account in the chart of accounts.
 */
class AccountRequest extends FormRequest
{
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
            'code' => ['required', 'string', 'max:10', 'regex:/^[0-9A-Za-z\-]+$/', Rule::unique(Account::class)->ignore($this->route('account'))],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * A system account keeps its type and stays active, because the app posts to it.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Account|null $account */
                $account = $this->route('account');

                if (! $account?->isSystem()) {
                    return;
                }

                if ($this->input('type') !== $account->type->value) {
                    $validator->errors()->add('type', __('The app posts to this account, so its type cannot change.'));
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', __('The app posts to this account, so it must stay active.'));
                }
            },
        ];
    }
}
