<?php

namespace App\Enums\Employee\Payroll;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum PayrollStatus: string implements HasColor
{
    use HasEnum;

    case INITIATED = 'initiated';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case FAILED = 'failed';

    public static function translation(): string
    {
        return 'employee.payroll.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::INITIATED => 'info',
            self::PROCESSING => 'warning',
            self::PROCESSED => 'success',
            self::FAILED => 'danger',
        };
    }
}
