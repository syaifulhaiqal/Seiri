<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Clocking in or out with the remote link. The browser adds its location when the employee allows it.
 */
class RemoteClockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'work_location' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array{latitude?: null|string, longitude?: null|string, work_location?: null|string}
     */
    public function location(): array
    {
        return $this->safe()->only(['latitude', 'longitude', 'work_location']);
    }
}
