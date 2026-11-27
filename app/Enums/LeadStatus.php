<?php

namespace App\Enums;

/**
 * Where a sales lead is in the pipeline.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Contacted => __('Contacted'),
            self::Qualified => __('Qualified'),
            self::Won => __('Won'),
            self::Lost => __('Lost'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Contacted => 'primary',
            self::Qualified => 'warning',
            self::Won => 'success',
            self::Lost => 'secondary',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Won, self::Lost], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
