<?php

namespace App\Enums\Library;

use App\Concerns\HasEnum;

enum IssueTo: string
{
    use HasEnum;

    case STUDENT = 'student';
    case EMPLOYEE = 'employee';

    public static function translation(): string
    {
        return 'library.transaction.to.';
    }
}
