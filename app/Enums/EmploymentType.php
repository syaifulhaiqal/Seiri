<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Permanent = 'permanent';
    case Contract = 'contract';
    case PartTime = 'part_time';
    case Intern = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::Permanent => __('Permanent'),
            self::Contract => __('Contract'),
            self::PartTime => __('Part-time'),
            self::Intern => __('Intern'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::Permanent => 'primary',
            self::Contract => 'info',
            self::PartTime => 'secondary',
            self::Intern => 'warning',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
