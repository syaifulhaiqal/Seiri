<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** Not needed any more: HR decided directly, an earlier approver rejected, or the request was cancelled. */
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Waiting'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::Skipped => __('Not needed'),
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
            self::Skipped => 'secondary',
        };
    }
}
