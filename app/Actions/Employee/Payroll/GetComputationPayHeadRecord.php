<?php

namespace App\Actions\Employee\Payroll;

use App\Enums\Employee\Payroll\PayHeadType;
use App\Helpers\SysHelper;
use App\Models\Employee\Payroll\SalaryStructure;
use App\Support\Evaluator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class GetComputationPayHeadRecord
{
    use Evaluator;

    public function execute(SalaryStructure $salaryStructure, array $records = []): array
    {
        $salaryTemplate = $salaryStructure->template;

        foreach ($salaryTemplate->records->where('type.value', PayHeadType::COMPUTATION->value)->sortBy('position') as $salaryTemplateRecord) {
            $computation = $salaryTemplateRecord->computation;

            foreach ($records as $record) {
                $computation = str_replace('#'.Arr::get($record, 'pay_head.code').'#', Arr::get($record, 'amount'), $computation);
            }

            $evaluation = $this->evaluate($computation);

            if ($evaluation === 'invalid') {
                throw ValidationException::withMessages(['message' => trans('employee.payroll.salary_template.invalid_computation')]);
            }

            if ($salaryTemplateRecord->getMeta('has_range', false)) {
                $minValue = $salaryTemplateRecord->getMeta('min_value', 0);
                $maxValue = $salaryTemplateRecord->getMeta('max_value', 0);

                if (! empty($minValue) && $evaluation < $minValue) {
                    $evaluation = $minValue;
                }

                if (! empty($maxValue) && $evaluation > $maxValue) {
                    $evaluation = $maxValue;
                }
            }

            array_push($records, [
                'pay_head' => [
                    'uuid' => $salaryTemplateRecord->payHead->uuid,
                    'name' => $salaryTemplateRecord->payHead->name,
                    'code' => $salaryTemplateRecord->payHead->code,
                    'category' => $salaryTemplateRecord->payHead->category->value,
                    'position' => $salaryTemplateRecord->position,
                    'is_user_defined' => $salaryTemplateRecord->is_user_defined,
                ],
                'amount' => SysHelper::formatAmount($evaluation),
            ]);
        }

        return $records;
    }
}
