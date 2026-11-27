<?php

namespace App\Notifications;

use App\Models\Role;

/**
 * To an admin: the super admin gave them a role (or made them super admin).
 */
class AdminRoleChanged extends AppNotification
{
    public function __construct(public null|Role $role)
    {
        parent::__construct();
    }

    protected function message(): string
    {
        return $this->role
            ? 'You have been given the :role role in the admin area.'
            : 'You are now a super admin, with access to every module.';
    }

    protected function parameters(): array
    {
        return ['role' => (string) $this->role?->name];
    }

    protected function url(): string
    {
        return route('admin.dashboard');
    }

    protected function icon(): string
    {
        return 'bi-key';
    }

    protected function subject(): string
    {
        return 'Your :app admin access has changed';
    }

    protected function actionText(): string
    {
        return 'Open the admin area';
    }
}
