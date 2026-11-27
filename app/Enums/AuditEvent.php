<?php

namespace App\Enums;

enum AuditEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Posted = 'posted';
    case Paid = 'paid';
    case Voided = 'voided';
    case Login = 'login';
    case Logout = 'logout';
    case LoginFailed = 'login_failed';

    public function label(): string
    {
        return match ($this) {
            self::Created => __('Created'),
            self::Updated => __('Updated'),
            self::Deleted => __('Deleted'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::Cancelled => __('Cancelled'),
            self::Posted => __('Posted'),
            self::Paid => __('Paid'),
            self::Voided => __('Voided'),
            self::Login => __('Signed in'),
            self::Logout => __('Signed out'),
            self::LoginFailed => __('Failed sign-in'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Created, self::Approved, self::Posted, self::Paid => 'success',
            self::Updated, self::Login, self::Logout => 'primary',
            self::Deleted, self::Rejected, self::Voided, self::LoginFailed => 'danger',
            self::Cancelled => 'secondary',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $event) => [$event->value => $event->label()])
            ->all();
    }
}
