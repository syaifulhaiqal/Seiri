<?php

namespace App\Http\Requests\Admin;

use App\Models\Holiday;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Add or change a public holiday.
 */
class HolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('holidays.manage');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', function (string $attribute, mixed $value, Closure $fail): void {
                $taken = Holiday::query()
                    ->whereDate('date', $value)
                    ->when($this->route('holiday'), fn ($query, Holiday $holiday) => $query->whereKeyNot($holiday->id))
                    ->exists();

                if ($taken) {
                    $fail(__('There is already a holiday on this date.'));
                }
            }],
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
