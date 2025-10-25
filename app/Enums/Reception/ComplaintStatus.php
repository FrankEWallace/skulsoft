<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum ComplaintStatus: string implements HasColor
{
    use HasEnum;

    case SUBMITTED = 'submitted';
    case IN_PROGRESS = 'in_progress';
    case REJECTED = 'rejected';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';

    public static function translation(): string
    {
        return 'reception.complaint.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::SUBMITTED => 'info',
            self::REJECTED => 'danger',
            self::IN_PROGRESS => 'warning',
            self::RESOLVED => 'success',
            self::CANCELLED => 'secondary',
        };
    }
}
