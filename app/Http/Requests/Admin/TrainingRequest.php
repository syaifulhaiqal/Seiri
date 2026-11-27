<?php

namespace App\Http\Requests\Admin;

use App\Enums\TrainingStatus;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HR adding or editing a training record.
 */
class TrainingRequest extends FormRequest
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
            'employee_id' => ['required', Rule::exists(Employee::class, 'id')],
            'title' => ['required', 'string', 'max:150'],
            'provider' => ['nullable', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'status' => ['required', Rule::enum(TrainingStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_id' => __('employee'),
        ];
    }

    /**
     * Validated data with an empty cost saved as zero.
     *
     * @return array<string, mixed>
     */
    public function training(): array
    {
        return [...$this->safe()->except('employee_id'), 'cost' => $this->validated('cost') ?? 0];
    }
}
