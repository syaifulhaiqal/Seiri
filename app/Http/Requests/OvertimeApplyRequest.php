<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * An employee claiming overtime they worked, up to 30 days back.
 */
class OvertimeApplyRequest extends FormRequest
{
    protected $errorBag = 'apply';

    public function authorize(): bool
    {
        return (bool) $this->user()?->employee?->isActive();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . today()->subDays(30)->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.before_or_equal' => __('Claim overtime after you have worked it.'),
            'date.after_or_equal' => __('Overtime can be claimed up to 30 days back.'),
        ];
    }

    /**
     * At most 12 hours, and no second claim for the same day.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (OvertimeRequest::hoursBetween($this->input('start_time'), $this->input('end_time')) > 12) {
                    $validator->errors()->add('end_time', __('One claim can cover 12 hours at most.'));

                    return;
                }

                /** @var Employee $employee */
                $employee = $this->user()->employee;

                if ($employee->overtimeRequests()->counted()->whereDate('date', $this->input('date'))->exists()) {
                    $validator->errors()->add('date', __('You already claimed overtime for this day.'));
                }
            },
        ];
    }
}
