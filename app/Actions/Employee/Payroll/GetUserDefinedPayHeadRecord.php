<?php

namespace App\Actions\Employee\Payroll;

use App\Enums\Employee\Payroll\PayHeadType;
use App\Models\Employee\Payroll\SalaryStructure;

class GetUserDefinedPayHeadRecord
{
    public function execute(SalaryStructure $salaryStructure, array $records = []): array
    {
        $salaryTemplate = $salaryStructure->template;

        foreach ($salaryTemplate->records->where('type.value', PayHeadType::USER_DEFINED->value)->sortBy('position') as $salaryTemplateRecord) {
            $records[] = [
                'pay_head' => [
                    'uuid' => $salaryTemplateRecord->payHead->uuid,
                    'name' => $salaryTemplateRecord->payHead->name,
                    'code' => $salaryTemplateRecord->payHead->code,
                    'category' => $salaryTemplateRecord->payHead->category->value,
                    'position' => $salaryTemplateRecord->position,
                    'is_user_defined' => $salaryTemplateRecord->is_user_defined,
                ],
                'amount' => 0,
            ];
        }

        return $records;
    }
}
