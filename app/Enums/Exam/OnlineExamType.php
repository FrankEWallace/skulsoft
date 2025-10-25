<?php

namespace App\Enums\Exam;

use App\Concerns\HasEnum;

enum OnlineExamType: string
{
    use HasEnum;

    case MCQ = 'mcq';
    case MIXED = 'mixed';

    public static function translation(): string
    {
        return 'exam.online_exam.types.';
    }
}
