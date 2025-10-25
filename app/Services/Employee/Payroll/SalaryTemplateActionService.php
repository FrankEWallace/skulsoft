<?php

namespace App\Services\Employee\Payroll;

use App\Actions\Employee\Payroll\SalaryStructureRecalculate;
use App\Models\Employee\Payroll\SalaryTemplate;

class SalaryTemplateActionService
{
    public function recalculate(SalaryTemplate $salaryTemplate)
    {
        return (new SalaryStructureRecalculate)->execute($salaryTemplate);
    }
}
