<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;

enum CallType: string
{
    use HasEnum;

    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';

    public static function translation(): string
    {
        return 'reception.call_log.types.';
    }
}
