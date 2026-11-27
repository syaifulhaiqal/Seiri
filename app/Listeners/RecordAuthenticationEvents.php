<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Sign-ins, sign-outs and failed sign-ins go into the audit trail. Passwords are never stored.
 */
class RecordAuthenticationEvents
{
    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            AuditLog::record(AuditEvent::Login, $event->user, description: $this->area($event->guard), user: $event->user);
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            AuditLog::record(AuditEvent::Logout, $event->user, description: $this->area($event->guard), user: $event->user);
        }
    }

    public function handleFailed(Failed $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        AuditLog::record(
            AuditEvent::LoginFailed,
            $user,
            new: ['email' => (string) ($event->credentials['email'] ?? '')],
            description: $this->area($event->guard),
            anonymous: true,
        );
    }

    protected function area(null|string $guard): string
    {
        return $guard === 'admin' ? __('Admin area') : __('User area');
    }
}
