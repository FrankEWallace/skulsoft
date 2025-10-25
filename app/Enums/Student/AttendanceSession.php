<?php

namespace App\Enums\Student;

use App\Concerns\HasEnum;

enum AttendanceSession: string
{
    use HasEnum;

    case FIRST = 'first';
    case SECOND = 'second';
    case THIRD = 'third';
    case FOURTH = 'fourth';
    case FIFTH = 'fifth';
    case SIXTH = 'sixth';
    case SEVENTH = 'seventh';
    case EIGHTH = 'eighth';
    case NINTH = 'ninth';
    case TENTH = 'tenth';

    public static function translation(): string
    {
        return 'list.numbers.';
    }
}
