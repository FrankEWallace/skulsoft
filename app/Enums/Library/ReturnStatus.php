<?php

namespace App\Enums\Library;

use App\Concerns\HasEnum;

enum ReturnStatus: string
{
    use HasEnum;

    case NORMAL = 'normal';
    case LATE = 'late';
    case DAMAGED = 'damaged';
    case LOST = 'lost';

    public static function translation(): string
    {
        return 'library.transaction.return_statuses.';
    }
}
