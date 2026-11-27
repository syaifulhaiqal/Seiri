<?php

namespace App\Http\Requests\Admin;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and update an employee.
 */
class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Blank pay fields mean zero.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'basic_salary' => filled($this->input('basic_salary')) ? $this->input('basic_salary') : 0,
            'monthly_pcb' => filled($this->input('monthly_pcb')) ? $this->input('monthly_pcb') : 0,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        return [
            'employee_no' => ['nullable', 'string', 'max:20', Rule::unique(Employee::class)->ignore($employee)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Employee::class)->ignore($employee)],
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'department_id' => ['nullable', Rule::exists(Department::class, 'id')],
            'job_title' => ['required', 'string', 'max:100'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'join_date' => ['required', 'date'],
            'end_date' => [
                Rule::requiredIf(fn () => $this->input('status') !== EmployeeStatus::Active->value),
                'nullable', 'date', 'after_or_equal:join_date',
            ],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'monthly_pcb' => ['required', 'numeric', 'min:0', 'max:999999'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\s\-]+$/'],
            // Only regular user accounts that are not already linked to someone else.
            'user_id' => [
                'nullable',
                Rule::exists(User::class, 'id')->where('role', UserRole::User->value),
                Rule::unique(Employee::class)->ignore($employee),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => __('The phone number may only contain digits, spaces, dashes and a leading +.'),
            'end_date.required' => __('Enter the last working day for an employee who has left.'),
            'user_id.unique' => __('That login is already linked to another employee.'),
            'bank_account_no.regex' => __('The account number may only contain digits, spaces and dashes.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'department_id' => __('department'),
            'user_id' => __('login account'),
            'employee_no' => __('employee number'),
            'monthly_pcb' => __('monthly PCB'),
            'bank_account_no' => __('bank account number'),
        ];
    }
}
