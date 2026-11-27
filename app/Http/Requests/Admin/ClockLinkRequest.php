<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create or change a shared clock-in link.
 */
class ClockLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('employees.manage');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:today'],
            'require_location' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'expires_on' => __('last day'),
        ];
    }
}
