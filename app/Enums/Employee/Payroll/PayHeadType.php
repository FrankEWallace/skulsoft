<?php

namespace App\Enums\Employee\Payroll;

use App\Concerns\HasEnum;

enum PayHeadType: string
{
    use HasEnum;

    case NA = 'not_applicable';
    case ATTENDANCE_BASED = 'attendance_based';
    case FLAT_RATE = 'flat_rate';
    case USER_DEFINED = 'user_defined';
    case COMPUTATION = 'computation';
    case PRODUCTION_BASED = 'production_based';

    public static function translation(): string
    {
        return 'employee.payroll.pay_head.types.';
    }

    public static function userInput(): array
    {
        return [self::ATTENDANCE_BASED->value, self::FLAT_RATE->value, self::PRODUCTION_BASED->value];
    }

    public static function toCalculate(): array
    {
        return [self::ATTENDANCE_BASED->value, self::FLAT_RATE->value, self::COMPUTATION->value];
    }
}
