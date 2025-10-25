<?php

namespace App\Enums\Exam;

use App\Concerns\HasEnum;

enum Result: string
{
    use HasEnum;

    case PASS = 'pass';
    case FAIL = 'fail';
    case REASSESSMENT = 'reassessment';

    public static function translation(): string
    {
        return 'exam.results.';
    }
}
