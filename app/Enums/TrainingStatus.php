<?php

namespace App\Enums;

enum TrainingStatus: string
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('Planned'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Planned => 'info',
            self::Completed => 'success',
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
