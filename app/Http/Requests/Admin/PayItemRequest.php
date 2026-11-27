<?php

namespace App\Http\Requests\Admin;

use App\Enums\PayItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add a monthly allowance or deduction to an employee.
 */
class PayItemRequest extends FormRequest
{
    protected $errorBag = 'payItem';

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
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(PayItemType::class)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ];
    }
}
