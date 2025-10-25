<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum Month: string
{
    use HasEnum;

    case JANUARY = 'january';
    case FEBRUARY = 'february';
    case MARCH = 'march';
    case APRIL = 'april';
    case MAY = 'may';
    case JUNE = 'june';
    case JULY = 'july';
    case AUGUST = 'august';
    case SEPTEMBER = 'september';
    case OCTOBER = 'october';
    case NOVEMBER = 'november';
    case DECEMBER = 'december';

    public static function translation(): string
    {
        return 'list.months.';
    }
}
