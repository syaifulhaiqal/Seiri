<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * An employee logging that they are leaving the office during work hours.
 */
class MovementRequest extends FormRequest
{
    protected $errorBag = 'movement';

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
            'date' => ['required', 'date', 'after_or_equal:' . today()->subDays(7)->toDateString(), 'before_or_equal:' . today()->addDays(30)->toDateString()],
            'time_out' => ['required', 'date_format:H:i'],
            'time_back' => ['nullable', 'date_format:H:i', 'after:time_out'],
            'destination' => ['required', 'string', 'max:150'],
            'purpose' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.after_or_equal' => __('Movements can be logged up to 7 days back.'),
            'time_back.after' => __('The return time must be after the time out.'),
        ];
    }
}
