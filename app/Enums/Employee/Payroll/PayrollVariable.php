<?php

namespace App\Enums\Employee\Payroll;

use App\Concerns\HasEnum;

enum PayrollVariable: string
{
    use HasEnum;

    case MONTHLY_DAYS = 'monthly_days';
    case WORKING_DAYS = 'working_days';
    case GROSS_EARNING = 'gross_earning';
    case GROSS_DEDUCTION = 'gross_deduction';
    case EMPLOYEE_CONTRIBUTION = 'employee_contribution';
    case EMPLOYER_CONTRIBUTION = 'employer_contribution';
    case EARNING_COMPONENT = 'earning_component';
    case DEDUCTION_COMPONENT = 'deduction_component';

    public static function translation(): string
    {
        return 'employee.payroll.variables.';
    }
}
