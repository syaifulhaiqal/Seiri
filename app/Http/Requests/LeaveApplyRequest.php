<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * An employee applying for leave from the self-service page.
 */
class LeaveApplyRequest extends FormRequest
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
            'leave_type_id' => ['required', Rule::exists(LeaveType::class, 'id')],
            'start_date' => ['required', 'date', 'after_or_equal:' . today()->subDays(30)->toDateString()],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => __('Leave can be backdated by 30 days at most.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'leave_type_id' => __('leave type'),
        ];
    }

    /**
     * Same-year dates, at least one working day, no overlap, and enough balance.
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

                /** @var Employee $employee */
                $employee = $this->user()->employee;
                $start = Carbon::parse($this->input('start_date'));
                $end = Carbon::parse($this->input('end_date'));
                $type = LeaveType::query()->findOrFail($this->input('leave_type_id'));

                if ($start->year !== $end->year) {
                    $validator->errors()->add('end_date', __('Split leave that crosses the new year into two requests.'));

                    return;
                }

                $days = LeaveRequest::workingDays($start, $end);

                if ($days === 0) {
                    $validator->errors()->add('end_date', __('These dates fall on a weekend or public holiday. Choose at least one working day.'));

                    return;
                }

                if ($employee->leaveRequests()->counted()->overlapping($start, $end)->exists()) {
                    $validator->errors()->add('start_date', __('You already have leave booked on some of these days.'));

                    return;
                }

                if (! $type->isUnlimited()) {
                    $usage = $employee->leaveUsage($type, $start->year);
                    $available = $type->days_per_year - $usage['approved'] - $usage['pending'];

                    if ($days > $available) {
                        $validator->errors()->add('end_date', trans_choice(
                            'This request is :days working day but you have :available left.|This request is :days working days but you have :available left.',
                            $days,
                            ['days' => $days, 'available' => max(0, $available)],
                        ));
                    }
                }
            },
        ];
    }
}
