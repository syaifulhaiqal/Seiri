<?php

namespace App\Enums;

enum ContactType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Customer => __('Customer'),
            self::Supplier => __('Supplier'),
            self::Both => __('Customer and supplier'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Customer => 'primary',
            self::Supplier => 'info',
            self::Both => 'secondary',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
