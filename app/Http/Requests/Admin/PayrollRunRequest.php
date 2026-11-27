<?php

namespace App\Http\Requests\Admin;

use App\Models\PayrollRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Start a payroll run for a month (input "2026-11").
 */
class PayrollRunRequest extends FormRequest
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
            'period' => ['required', 'date_format:Y-m', 'before_or_equal:' . today()->addMonth()->format('Y-m')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.before_or_equal' => __('Payroll can be prepared up to one month ahead.'),
        ];
    }

    public function period(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->validated('period'))->startOfMonth();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (PayrollRun::query()->whereDate('period', $this->period())->exists()) {
                    $validator->errors()->add('period', __('There is already a payroll run for :month.', ['month' => $this->period()->format('F Y')]));
                }
            },
        ];
    }
}
