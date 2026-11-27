<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and update an admin role. Only the super admin gets here (can:roles.manage on the route).
 */
class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('roles.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['permissions' => array_values((array) $this->input('permissions', []))]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Role::class)->ignore($this->route('role'))],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['distinct', Rule::enum(Permission::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permissions.required' => __('Tick at least one permission.'),
        ];
    }
}
