<?php

namespace App\Enums\Student;

use App\Concerns\HasEnum;

enum AdmissionType: string
{
    use HasEnum;

    case PROVISIONAL = 'provisional';
    case REGULAR = 'regular';

    public static function translation(): string
    {
        return 'student.admission.types.';
    }
}
