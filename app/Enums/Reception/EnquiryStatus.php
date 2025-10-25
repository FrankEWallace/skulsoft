<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum EnquiryStatus: string implements HasColor
{
    use HasEnum;

    case OPEN = 'open';
    // case PARTIALLY_CLOSED = 'partially_closed';
    case MISSED = 'missed';
    case CLOSE = 'close';

    public static function translation(): string
    {
        return 'reception.enquiry.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'info',
            // self::PARTIALLY_CLOSED => 'warning',
            self::MISSED => 'danger',
            self::CLOSE => 'success',
        };
    }
}
