<?php

namespace App\Enums\Student;

use App\Concerns\HasEnum;

enum AudienceType: string
{
    use HasEnum;

    case ALL = 'all';
    case DIVISION_WISE = 'division_wise';
    case COURSE_WISE = 'course_wise';
    case BATCH_WISE = 'batch_wise';

    public static function translation(): string
    {
        return 'student.audience_types.';
    }
}
