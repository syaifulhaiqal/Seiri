<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Invoiced = 'invoiced';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Sent => __('Sent'),
            self::Accepted => __('Accepted'),
            self::Declined => __('Declined'),
            self::Invoiced => __('Invoiced'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Sent => 'warning',
            self::Accepted => 'success',
            self::Declined => 'danger',
            self::Invoiced => 'primary',
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
