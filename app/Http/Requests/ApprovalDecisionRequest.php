<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An approver approving or rejecting a step in an approval chain.
 */
class ApprovalDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('approval')->approver_id === $this->user()->id;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'required_if:decision,reject', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required_if' => __('Give the employee a reason when rejecting.'),
        ];
    }

    public function approves(): bool
    {
        return $this->validated('decision') === 'approve';
    }
}
