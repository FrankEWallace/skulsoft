<?php

namespace App\Actions\Employee\Payroll;

use App\Enums\Employee\Payroll\PayHeadType;
use App\Helpers\CalHelper;
use App\Helpers\SysHelper;
use App\Models\Employee\Payroll\SalaryStructure;

class GetFlatRatePayHeadRecord
{
    public function execute(SalaryStructure $salaryStructure, string $startDate, string $endDate, array $records = []): array
    {
        $salaryTemplate = $salaryStructure->template;

        foreach ($salaryTemplate->records->where('type.value', PayHeadType::FLAT_RATE->value) as $salaryTemplateRecord) {
            $monthlySalary = $salaryStructure->records->firstWhere('pay_head_id', $salaryTemplateRecord->pay_head_id)->amount?->value ?? 0;
            $days = CalHelper::dateDiff($startDate, $endDate);
            $salary = SysHelper::formatAmount(($monthlySalary / 30) * ($days));

            array_push($records, [
                'pay_head' => [
                    'uuid' => $salaryTemplateRecord->payHead->uuid,
                    'name' => $salaryTemplateRecord->payHead->name,
                    'code' => $salaryTemplateRecord->payHead->code,
                    'category' => $salaryTemplateRecord->payHead->category->value,
                    'position' => $salaryTemplateRecord->position,
                    'is_user_defined' => $salaryTemplateRecord->is_user_defined,
                ],
                'amount' => $salary,
            ]);
        }

        return $records;
    }
}
