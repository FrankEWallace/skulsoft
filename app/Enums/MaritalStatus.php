<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum MaritalStatus: string
{
    use HasEnum;

    case DIVORCED = 'divorced';
    case SEPARATED = 'separated';
    case SINGLE = 'single';
    case WIDOWED = 'widowed';
    case MARRIED = 'married';

    public static function translation(): string
    {
        return 'list.marital_statuses.';
    }
}
