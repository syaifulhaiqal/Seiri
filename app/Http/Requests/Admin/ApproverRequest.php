<?php

namespace App\Http\Requests\Admin;

use App\Enums\ApprovalType;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * An employee's approval chains: up to three approvers per kind of request, in order.
 */
class ApproverRequest extends FormRequest
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
        /** @var Employee $employee */
        $employee = $this->route('employee');

        $rules = [];

        foreach (ApprovalType::cases() as $type) {
            $rules["approvers.{$type->value}"] = ['nullable', 'array', 'max:' . ApprovalType::LEVELS];
            $rules["approvers.{$type->value}.*"] = [
                'nullable',
                'integer',
                Rule::in(array_keys(self::choices($employee))),
            ];
        }

        return $rules;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (ApprovalType::cases() as $type) {
                    $chosen = array_filter($this->input("approvers.{$type->value}", []));

                    if (count($chosen) !== count(array_unique($chosen))) {
                        $validator->errors()->add("approvers.{$type->value}", __('Choose a different person at each level.'));
                    }
                }
            },
        ];
    }

    /**
     * The chains to save: type => [level => approver user id], with gaps closed up.
     *
     * @return array<string, array<int, int>>
     */
    public function chains(): array
    {
        return collect(ApprovalType::cases())->mapWithKeys(fn (ApprovalType $type) => [
            $type->value => collect($this->validated("approvers.{$type->value}", []))
                ->filter()
                ->values()
                ->mapWithKeys(fn ($userId, int $index) => [$index + 1 => (int) $userId])
                ->all(),
        ])->all();
    }

    /**
     * Who can approve: staff with a login, other than the employee.
     *
     * @return array<int, string> user id => name
     */
    public static function choices(Employee $employee): array
    {
        return Employee::query()
            ->active()
            ->whereNotNull('user_id')
            ->whereKeyNot($employee->id)
            ->orderBy('name')
            ->get(['name', 'job_title', 'user_id'])
            ->mapWithKeys(fn (Employee $approver) => [$approver->user_id => $approver->name . ' · ' . $approver->job_title])
            ->all();
    }
}
