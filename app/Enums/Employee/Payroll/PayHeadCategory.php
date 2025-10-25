<?php

namespace App\Enums\Employee\Payroll;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum PayHeadCategory: string implements HasColor
{
    use HasEnum;

    case EARNING = 'earning';
    case DEDUCTION = 'deduction';
    case EMPLOYEE_CONTRIBUTION = 'employee_contribution';
    case EMPLOYER_CONTRIBUTION = 'employer_contribution';

    public static function translation(): string
    {
        return 'employee.payroll.pay_head.categories.';
    }

    public function color(): string
    {
        return match ($this) {
            self::EARNING => 'success',
            self::DEDUCTION => 'danger',
            self::EMPLOYEE_CONTRIBUTION => 'info',
            self::EMPLOYER_CONTRIBUTION => 'info',
        };
    }
}
