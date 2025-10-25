<?php

namespace App\Enums\Approval;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum Status: string implements HasColor
{
    use HasEnum;

    case REQUESTED = 'requested';
    case RETURNED = 'returned';
    case HOLD = 'hold';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public static function translation(): string
    {
        return 'approval.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::REQUESTED => 'info',
            self::RETURNED => 'warning',
            self::HOLD => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'danger',
        };
    }
}
