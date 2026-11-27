<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case Resigned = 'resigned';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Resigned => __('Resigned'),
            self::Terminated => __('Terminated'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Resigned => 'secondary',
            self::Terminated => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
