<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum Gender: string
{
    use HasEnum;

    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';

    public static function translation(): string
    {
        return 'list.genders.';
    }
}
