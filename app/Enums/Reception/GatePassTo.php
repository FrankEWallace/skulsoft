<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;

enum GatePassTo: string
{
    use HasEnum;

    case STUDENT = 'student';
    case EMPLOYEE = 'employee';

    public static function translation(): string
    {
        return 'reception.gate_pass.to.';
    }
}
