<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesLineItems;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Raising or editing a purchase request.
 */
class PurchaseRequestRequest extends FormRequest
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
            'purpose' => ['required', 'string', 'max:500'],
            'needed_by' => ['nullable', 'date'],
            ...$this->lineRules(),
        ];
    }
}
