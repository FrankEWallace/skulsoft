<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;

enum VisitorType: string
{
    use HasEnum;

    case STUDENT = 'student';
    case GUARDIAN = 'guardian';
    case EMPLOYEE = 'employee';
    case OTHER = 'other';

    public static function translation(): string
    {
        return 'reception.visitor_log.types.';
    }
}
