<?php

namespace App\Enums;

/**
 * A touchpoint with a lead.
 */
enum ActivityType: string
{
    case Call = 'call';
    case Meeting = 'meeting';
    case Email = 'email';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Call => __('Call'),
            self::Meeting => __('Meeting'),
            self::Email => __('Email'),
            self::Note => __('Note'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call => 'bi-telephone',
            self::Meeting => 'bi-people',
            self::Email => 'bi-envelope',
            self::Note => 'bi-sticky',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
