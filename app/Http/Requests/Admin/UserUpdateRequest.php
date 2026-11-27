<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserUpdateRequest extends FormRequest
{
    /**
     * Admins with the Users permission manage ordinary user accounts. Only the super admin edits admins.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.edit', $this->route('user'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($target->id)],
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            // Only the super admin assigns roles; for anyone else these fields are ignored.
            'role' => [Rule::excludeIf(! $this->assignsRoles()), 'required', Rule::enum(UserRole::class)],
            'admin_role_id' => [
                Rule::excludeIf(! $this->assignsRoles() || $this->input('role') !== UserRole::Admin->value),
                'required',
                Rule::exists(Role::class, 'id'),
            ],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => __('The phone number may only contain digits, spaces, dashes and a leading +.'),
            'admin_role_id.required' => __('Choose which role this admin has. It decides the modules they can use.'),
        ];
    }

    /**
     * Stop an admin from locking themselves out.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->route('user')->is($this->user())) {
                    return;
                }

                if ($this->assignsRoles() && $this->input('role') !== $this->user()->role->value) {
                    $validator->errors()->add('role', __('You cannot change your own role.'));
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', __('You cannot deactivate your own account.'));
                }
            },
        ];
    }

    public function assignsRoles(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }
}
