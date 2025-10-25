<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum Locality: string
{
    use HasEnum;

    case URBAN = 'urban';
    case RURAL = 'rural';

    public static function translation(): string
    {
        return 'contact.localities.';
    }
}
