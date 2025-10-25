<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum ServiceRequestType: string
{
    use HasEnum;

    case OPT_IN = 'opt_in';
    case OPT_OUT = 'opt_out';

    public static function translation(): string
    {
        return 'service.request.types.';
    }
}
