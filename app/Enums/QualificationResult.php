<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum QualificationResult: string
{
    use HasEnum;

    case PASS = 'pass';
    case FAIL = 'fail';
    case REAPPEAR = 'reappear';
    case AWAITING_RESULT = 'awaiting_result';
    case OTHER = 'other';

    public static function translation(): string
    {
        return 'student.qualification.results.';
    }
}
