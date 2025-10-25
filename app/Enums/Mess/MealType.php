<?php

namespace App\Enums\Mess;

use App\Concerns\HasEnum;

enum MealType: string
{
    use HasEnum;

    case BREAKFAST = 'breakfast';
    case LUNCH = 'lunch';
    case SNACKS = 'snacks';
    case DINNER = 'dinner';

    public static function translation(): string
    {
        return 'mess.meal_types.';
    }
}
