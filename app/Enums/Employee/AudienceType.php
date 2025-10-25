<?php

namespace App\Enums\Employee;

use App\Concerns\HasEnum;

enum AudienceType: string
{
    use HasEnum;

    case ALL = 'all';
    case DEPARTMENT_WISE = 'department_wise';
    case DESIGNATION_WISE = 'designation_wise';

    public static function translation(): string
    {
        return 'employee.audience_types.';
    }
}
