<?php

namespace App\Enums\Academic;

use App\Concerns\HasEnum;

enum IdCardFor: string
{
    use HasEnum;

    case STUDENT = 'student';
    case EMPLOYEE = 'employee';
    case GUARDIAN = 'guardian';

    public static function translation(): string
    {
        return 'academic.id_card.for.';
    }
}
