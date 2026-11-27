<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Cheque = 'cheque';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => __('Bank transfer'),
            self::Cash => __('Cash'),
            self::Cheque => __('Cheque'),
            self::Card => __('Card'),
        };
    }

    /**
     * Bootstrap colour used for badges.
     */
    public function context(): string
    {
        return match ($this) {
            self::BankTransfer => 'primary',
            self::Cash => 'success',
            self::Cheque => 'info',
            self::Card => 'secondary',
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
