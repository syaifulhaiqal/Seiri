<?php

namespace App\Enums;

/**
 * The kinds of request that can go through an employee's approval chain.
 */
enum ApprovalType: string
{
    case Leave = 'leave';
    case Claim = 'claim';
    case Overtime = 'overtime';

    public function label(): string
    {
        return match ($this) {
            self::Leave => __('Leave'),
            self::Claim => __('Expense claims'),
            self::Overtime => __('Overtime'),
        };
    }

    /**
     * Highest number of approvers in one chain.
     */
    public const int LEVELS = 3;
}
