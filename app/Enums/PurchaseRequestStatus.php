<?php

namespace App\Enums;

enum PurchaseRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Ordered = 'ordered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Waiting for approval'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::Ordered => __('Ordered'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Ordered => 'primary',
            self::Cancelled => 'secondary',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
