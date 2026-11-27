<?php

namespace App\Http\Requests\Admin;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and update a leave type.
 */
class LeaveTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50', Rule::unique(LeaveType::class)->ignore($this->route('leave_type'))],
            'days_per_year' => ['required', 'integer', 'min:0', 'max:365'],
            'is_paid' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'days_per_year' => __('days per year'),
        ];
    }
}
