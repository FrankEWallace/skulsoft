<?php

namespace App\Enums\Employee;

use App\Concerns\HasEnum;

enum Type: string
{
    use HasEnum;

    case ADMINISTRATIVE = 'administrative';
    case TEACHING = 'teaching';
    case SUPPORT = 'support';

    public static function translation(): string
    {
        return 'employee.types.';
    }
}
