<?php

namespace App\Enums\Employee\Attendance;

use App\Concerns\HasEnum;

enum ProductionUnit: string
{
    use HasEnum;

    case HOURLY = 'hourly';

    public static function translation(): string
    {
        return 'employee.attendance.production_units.';
    }
}
