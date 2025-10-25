<?php

namespace App\Actions\Employee\Payroll;

use App\Enums\ComparisonOperator;
use App\Enums\Employee\Payroll\PayHeadCategory;
use App\Enums\Employee\Payroll\PayHeadType;
use App\Enums\Employee\Payroll\PayrollVariable;
use App\Enums\LogicalOperator;
use App\Helpers\SysHelper;
use App\Models\Employee\Payroll\SalaryTemplate;
use App\Models\Employee\Payroll\SalaryTemplateRecord;
use App\Support\Evaluator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CalculatePayHeads
{
    use Evaluator;

    protected $roundOff = true;

    public function execute(SalaryTemplate $salaryTemplate, array $records, array $params = [])
    {
        $payHeads = [];

        $earningSalaryTemplateRecords = $salaryTemplate->records->filter(function ($record) {
            return $record->payHead->category == PayHeadCategory::EARNING;
        })->sortBy('position');

        $deductionSalaryTemplateRecords = $salaryTemplate->records->filter(function ($record) {
            return $record->payHead->category == PayHeadCategory::DEDUCTION;
        })->sortBy('position');

        $employeeContributionSalaryTemplateRecords = $salaryTemplate->records->filter(function ($record) {
            return $record->payHead->category == PayHeadCategory::EMPLOYEE_CONTRIBUTION;
        })->sortBy('position');

        $employerContributionSalaryTemplateRecords = $salaryTemplate->records->filter(function ($record) {
            return $record->payHead->category == PayHeadCategory::EMPLOYER_CONTRIBUTION;
        })->sortBy('position');

        foreach ($earningSalaryTemplateRecords as $record) {
            $payHeads = $this->getPayHeads($record, $payHeads, $records, $params);
            $payHeads = $this->getComputationalPayHeads($record, $payHeads, $params);
        }

        if (array_key_exists('gross_earning', $params)) {
            $grossEarning = Arr::get($params, 'gross_earning', 0);
        } else {
            $grossEarning = collect($payHeads)->where('category', 'earning')
                ->where('as_total', false)
                ->sum('amount');
        }

        $grossEarning = $this->formatAmount($grossEarning);
        $earningComponent = collect($payHeads)->where('category', 'earning')
            ->where('as_total', false)
            ->sum('amount');
        $earningComponent = $this->formatAmount($earningComponent);

        $payHeads[] = [
            'type' => 'gross',
            'category' => 'gross',
            'code' => 'GROSS_EARNING',
            'amount' => $grossEarning,
        ];

        $payHeads[] = [
            'type' => 'component',
            'category' => 'component',
            'code' => 'EARNING_COMPONENT',
            'amount' => $earningComponent,
        ];

        foreach ($deductionSalaryTemplateRecords as $record) {
            $payHeads = $this->getPayHeads($record, $payHeads, $records, $params);
            $payHeads = $this->getComputationalPayHeads($record, $payHeads, $params);
        }

        if (array_key_exists('gross_deduction', $params)) {
            $grossDeduction = Arr::get($params, 'gross_deduction', 0);
        } else {
            $grossDeduction = collect($payHeads)->where('category', 'deduction')
                ->where('as_total', false)
                ->sum('amount');
        }

        $grossDeduction = $this->formatAmount($grossDeduction);
        $deductionComponent = collect($payHeads)->where('category', 'deduction')
            ->where('as_total', false)
            ->sum('amount');
        $deductionComponent = $this->formatAmount($deductionComponent);

        $payHeads[] = [
            'type' => 'gross',
            'category' => 'gross',
            'code' => 'GROSS_DEDUCTION',
            'amount' => $grossDeduction,
        ];

        $payHeads[] = [
            'type' => 'component',
            'category' => 'component',
            'code' => 'DEDUCTION_COMPONENT',
            'amount' => $deductionComponent,
        ];

        foreach ($employeeContributionSalaryTemplateRecords as $record) {
            $payHeads = $this->getPayHeads($record, $payHeads, $records, $params);
            $payHeads = $this->getComputationalPayHeads($record, $payHeads, $params);
        }

        $employeeContribution = collect($payHeads)->where('category', PayrollVariable::EMPLOYEE_CONTRIBUTION->value)->sum('amount');
        $employeeContribution = $this->formatAmount($employeeContribution);

        $payHeads[] = [
            'type' => 'component',
            'category' => 'component',
            'code' => 'EMPLOYEE_CONTRIBUTION',
            'amount' => $employeeContribution,
        ];

        foreach ($employerContributionSalaryTemplateRecords as $record) {
            $payHeads = $this->getPayHeads($record, $payHeads, $records, $params);
            $payHeads = $this->getComputationalPayHeads($record, $payHeads, $params);
        }

        $employerContribution = collect($payHeads)->where('category', PayrollVariable::EMPLOYER_CONTRIBUTION->value)->sum('amount');
        $employerContribution = $this->formatAmount($employerContribution);

        $payHeads[] = [
            'type' => 'component',
            'category' => 'component',
            'code' => 'EMPLOYER_CONTRIBUTION',
            'amount' => $employerContribution,
        ];

        return [
            'earning_component' => $earningComponent,
            'deduction_component' => $deductionComponent,
            'employee_contribution' => $employeeContribution,
            'employer_contribution' => $employerContribution,
            'pay_heads' => $payHeads,
        ];
    }

    private function getPayHeads(SalaryTemplateRecord $record, array $payHeads = [], array $records = [], array $params = [])
    {
        $inputRecords = collect($records);
        $inputRecord = $inputRecords->firstWhere('pay_head', $record->payHead->uuid);
        $inputRecordAmount = Arr::get($inputRecord, 'amount', 0);

        if ($record->type == PayHeadType::NA) {
            return $payHeads;
        }

        if ($record->type == PayHeadType::FLAT_RATE) {
            $payHeads[] = [
                'id' => $record->pay_head_id,
                'uuid' => $record->payHead->uuid,
                'name' => $record->payHead->name,
                'type' => $record->type->value,
                'category' => $record->payHead->category->value,
                'code' => $record->payHead->code,
                'amount' => $inputRecordAmount,
                'as_total' => $record->as_total,
                'visibility' => $record->visibility,
            ];
        } elseif ($record->type == PayHeadType::ATTENDANCE_BASED) {
            $payHeads[] = [
                'id' => $record->pay_head_id,
                'uuid' => $record->payHead->uuid,
                'name' => $record->payHead->name,
                'type' => $record->type->value,
                'category' => $record->payHead->category->value,
                'code' => $record->payHead->code,
                'amount' => $this->formatAmount(($inputRecordAmount / $params['monthly_days']) * $params['working_days']),
                'as_total' => $record->as_total,
                'visibility' => $record->visibility,
            ];
        } elseif ($record->type == PayHeadType::USER_DEFINED) {
            $payHeads[] = [
                'id' => $record->pay_head_id,
                'uuid' => $record->payHead->uuid,
                'name' => $record->payHead->name,
                'type' => $record->type->value,
                'category' => $record->payHead->category->value,
                'code' => $record->payHead->code,
                'amount' => $inputRecordAmount,
                'is_user_defined' => true,
                'as_total' => $record->as_total,
                'visibility' => $record->visibility,
            ];
        } elseif ($record->type == PayHeadType::PRODUCTION_BASED) {
            $attendanceRecords = Arr::get($params, 'attendance_records', collect([]));

            $value = $attendanceRecords->where('attendance_type_id', $record->attendance_type_id)->sum('value');
            $amount = $this->formatAmount($value * $inputRecordAmount);

            $payHeads[] = [
                'id' => $record->pay_head_id,
                'uuid' => $record->payHead->uuid,
                'name' => $record->payHead->name,
                'type' => $record->type->value,
                'category' => $record->payHead->category->value,
                'code' => $record->payHead->code,
                'amount' => $amount,
                'as_total' => $record->as_total,
                'visibility' => $record->visibility,
            ];
        }

        return $payHeads;
    }

    private function getComputationalPayHeads(SalaryTemplateRecord $record, array $payHeads = [], array $params = [])
    {
        if ($record->type != PayHeadType::COMPUTATION) {
            return $payHeads;
        }

        $payHeads = $this->evaluateNonConditionalFormula($record, $payHeads, $params);
        $payHeads = $this->evaluateConditionalFormula($record, $payHeads, $params);

        return $payHeads;
    }

    private function evaluateNonConditionalFormula(SalaryTemplateRecord $record, array $payHeads, array $params = [])
    {
        if (! Arr::get($params, 'force_apply', false)) {
            return $payHeads;

            if ($record->getMeta('has_condition', false)) {
                return $payHeads;
            }
        }

        $formula = $record->computation;
        $actualFormula = $formula;

        $monthlyDays = Arr::get($params, 'monthly_days', 30);
        $workingDays = Arr::get($params, 'working_days', 30);

        $formula = str_replace('#MONTHLY_DAYS#', $monthlyDays, $formula);
        $formula = str_replace('#WORKING_DAYS#', $workingDays, $formula);

        foreach ($payHeads as $payHead) {
            $formula = str_replace('#'.$payHead['code'].'#', $payHead['amount'], $formula);
        }

        $evaluation = $this->evaluate($formula);

        if ($evaluation === 'invalid') {
            throw ValidationException::withMessages(['message' => trans('employee.payroll.salary_template.invalid_computation')]);
        }

        $evaluation = $this->formatAmount($evaluation);

        if ($record->getMeta('has_range', false)) {
            $minValue = $record->getMeta('min_value', 0);
            $maxValue = $record->getMeta('max_value', 0);

            if ($minValue > 0 && $evaluation < $minValue) {
                $evaluation = $minValue;
            }

            if ($maxValue > 0 && $evaluation > $maxValue) {
                $evaluation = $maxValue;
            }
        }

        $payHeads[] = [
            'id' => $record->pay_head_id,
            'uuid' => $record->payHead->uuid,
            'name' => $record->payHead->name,
            'type' => $record->type->value,
            'category' => $record->payHead->category->value,
            'code' => $record->payHead->code,
            'amount' => $evaluation,
            'applied_formula' => $formula,
            'formula' => $actualFormula,
            'as_total' => $record->as_total,
            'visibility' => $record->visibility,
        ];

        return $payHeads;
    }

    private function evaluateConditionalFormula(SalaryTemplateRecord $record, array $payHeads, array $params = [])
    {
        $monthlyDays = Arr::get($params, 'monthly_days', 30);
        $workingDays = Arr::get($params, 'working_days', 30);

        $formulaApplied = false;

        $conditionalFormulas = $record->getMeta('conditional_formulas', []);
        foreach ($conditionalFormulas as $conditionalFormula) {
            $allConditionsMet = false;
            $anyConditionMet = false;

            $formula = $conditionalFormula['formula'] ?? '';
            $actualFormula = $formula;
            $conditions = $conditionalFormula['conditions'] ?? [];

            $formula = str_replace('#MONTHLY_DAYS#', $monthlyDays, $formula);
            $formula = str_replace('#WORKING_DAYS#', $workingDays, $formula);

            foreach ($payHeads as $payHead) {
                $formula = str_replace('#'.$payHead['code'].'#', $payHead['amount'], $formula);
            }

            foreach ($conditions as $conditionIndex => $condition) {
                $referencePayHead = Arr::get($condition, 'reference_pay_head');
                $operator = ComparisonOperator::tryFrom(Arr::get($condition, 'operator'));
                $logicalOperator = LogicalOperator::tryFrom(Arr::get($condition, 'logical_operator'));
                $value = Arr::get($condition, 'value');

                $referencePayHeadAmount = collect($payHeads)->where('code', $referencePayHead)->first()['amount'] ?? 0;

                $conditionMet = $this->evaluateCondition($referencePayHeadAmount, $operator, $value);

                if ($conditionIndex === 0) {
                    $allConditionsMet = $conditionMet;
                } else {
                    if ($logicalOperator === LogicalOperator::AND) {
                        $allConditionsMet = $allConditionsMet && $conditionMet;
                    } elseif ($logicalOperator === LogicalOperator::OR) {
                        $anyConditionMet = $anyConditionMet || $conditionMet;
                    }
                }
            }

            $conditionsAreMet = $allConditionsMet || $anyConditionMet;

            if ($conditionsAreMet) {
                $evaluation = $this->evaluate($formula);

                if ($evaluation === 'invalid') {
                    throw ValidationException::withMessages(['message' => trans('employee.payroll.salary_template.invalid_computation')]);
                }

                $evaluation = $this->formatAmount($evaluation);

                if ($record->getMeta('has_range', false)) {
                    $minValue = $record->getMeta('min_value', 0);
                    $maxValue = $record->getMeta('max_value', 0);

                    if ($minValue > 0 && $evaluation < $minValue) {
                        $evaluation = $minValue;
                    }

                    if ($maxValue > 0 && $evaluation > $maxValue) {
                        $evaluation = $maxValue;
                    }
                }

                $payHeads[] = [
                    'id' => $record->pay_head_id,
                    'uuid' => $record->payHead->uuid,
                    'name' => $record->payHead->name,
                    'type' => $record->type->value,
                    'category' => $record->payHead->category->value,
                    'code' => $record->payHead->code,
                    'amount' => $evaluation,
                    'applied_formula' => $formula,
                    'formula' => $actualFormula,
                    'as_total' => $record->as_total,
                    'visibility' => $record->visibility,
                ];

                $formulaApplied = true;

                break;
            }
        }

        if (! $formulaApplied) {
            $params['force_apply'] = true;
            $payHeads = $this->evaluateNonConditionalFormula($record, $payHeads, $params);
        }

        return $payHeads;
    }

    private function evaluateCondition($value, ComparisonOperator $operator, $comparisonValue)
    {
        switch ($operator) {
            case ComparisonOperator::EQUAL:
                return $value == $comparisonValue;
            case ComparisonOperator::NOT_EQUAL:
                return $value != $comparisonValue;
            case ComparisonOperator::GREATER_THAN:
                return $value > $comparisonValue;
            case ComparisonOperator::LESS_THAN:
                return $value < $comparisonValue;
            case ComparisonOperator::GREATER_THAN_OR_EQUAL:
                return $value >= $comparisonValue;
            case ComparisonOperator::LESS_THAN_OR_EQUAL:
                return $value <= $comparisonValue;
            default:
                return false;
        }
    }

    private function formatAmount($amount)
    {
        if (config('config.employee.enable_payhead_round_off')) {
            return round(SysHelper::formatAmount($amount));
        }

        return SysHelper::formatAmount($amount);
    }
}
