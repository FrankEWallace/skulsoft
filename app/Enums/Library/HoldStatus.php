<?php

namespace App\Enums\Library;

use App\Concerns\HasEnum;

enum HoldStatus: string
{
    use HasEnum;

    case DAMAGED = 'damaged';
    case LOST = 'lost';

    public static function translation(): string
    {
        return 'library.transaction.hold_statuses.';
    }
}
