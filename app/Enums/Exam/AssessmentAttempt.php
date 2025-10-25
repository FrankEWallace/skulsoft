<?php

namespace App\Enums\Exam;

use App\Concerns\HasEnum;

enum AssessmentAttempt: string
{
    use HasEnum;

    case FIRST = 'first';
    case SECOND = 'second';
    case THIRD = 'third';
    case FOURTH = 'fourth';
    case FIFTH = 'fifth';

    public static function translation(): string
    {
        return 'exam.schedule.attempts.';
    }

    public static function getAttempt($value): string
    {
        return match ($value) {
            1 => 'first',
            2 => 'second',
            3 => 'third',
            4 => 'fourth',
            5 => 'fifth',
        };
    }

    public static function getAttemptNumber($value): int
    {
        return match ($value) {
            'first' => 1,
            'second' => 2,
            'third' => 3,
            'fourth' => 4,
            'fifth' => 5,
        };
    }
}
