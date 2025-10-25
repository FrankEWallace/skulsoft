<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum QueryStatus: string implements HasColor
{
    use HasEnum;

    case SUBMITTED = 'submitted';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';

    public static function translation(): string
    {
        return 'reception.query.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::SUBMITTED => 'info',
            self::IN_PROGRESS => 'warning',
            self::RESOLVED => 'success',
        };
    }
}
