<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;

enum CorrespondenceType: string
{
    use HasEnum;

    case INWARD = 'inward';
    case OUTWARD = 'outward';

    public static function translation(): string
    {
        return 'reception.correspondence.types.';
    }
}
