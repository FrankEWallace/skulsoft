<?php

namespace App\Enums\Exam;

use App\Concerns\HasEnum;

enum ReportType: string
{
    use HasEnum;

    case MARK_BASED = 'mark_based';
    case CREDIT_BASED = 'credit_based';

    public static function translation(): string
    {
        return 'exam.report_types.';
    }
}
