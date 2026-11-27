<?php

namespace App\Http\Requests\Admin;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adding or editing a sales lead.
 */
class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['value' => filled($this->input('value')) ? $this->input('value') : 0]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'contact_name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'source' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'value' => ['numeric', 'min:0', 'max:999999999'],
            'expected_close' => ['nullable', 'date'],
            'owner_id' => ['nullable', Rule::exists(User::class, 'id')->whereIn('role', [UserRole::Admin->value, UserRole::SuperAdmin->value])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'owner_id' => __('owner'),
        ];
    }
}
