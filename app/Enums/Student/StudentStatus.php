<?php

namespace App\Enums\Student;

use App\Concerns\HasEnum;

enum StudentStatus: string
{
    use HasEnum;

    case STUDYING = 'studying';
    case CANCELLED = 'cancelled';
    case TRANSFERRED = 'transferred';

    public static function translation(): string
    {
        return 'student.statuses.';
    }
}
