<?php

namespace App\Actions\Employee\Payroll;

use App\Models\Employee\Payroll\PayHead;
use App\Models\Employee\Payroll\Payroll;
use App\Models\Employee\Payroll\SalaryStructure;
use App\Models\Employee\Payroll\SalaryTemplate;
use Illuminate\Support\Arr;

class SalaryStructureRecalculate
{
    public function execute(SalaryTemplate $salaryTemplate)
    {
        if ($salaryTemplate->has_hourly_payroll) {
            return [
                'status' => 'error',
                'message' => __('general.errors.invalid_action'),
            ];
        }

        $salaryStructures = SalaryStructure::query()
            ->where('salary_template_id', $salaryTemplate->id)
            ->get();

        $existingPayrolls = Payroll::query()
            ->whereIn('salary_structure_id', $salaryStructures->pluck('id'))
            ->exists();

        if ($existingPayrolls) {
            return [
                'status' => 'error',
                'message' => __('employee.payroll.salary_structure.could_not_perform_if_payroll_generated_with_salary_structure'),
            ];
        }

        $payHeads = PayHead::query()
            ->byTeam()
            ->get();

        \DB::beginTransaction();

        foreach ($salaryStructures as $salaryStructure) {
            $records = $salaryStructure->records->map(function ($record) use ($payHeads) {
                $payHead = $payHeads->firstWhere('id', $record->pay_head_id);

                return [
                    'pay_head' => $payHead->uuid,
                    'amount' => $record->amount->value,
                ];
            })->toArray();

            $params = [
                'monthly_days' => 30,
                'working_days' => 30,
            ];

            $data = (new CalculatePayHeads)->execute($salaryTemplate, $records, $params);

            $earningComponent = Arr::get($data, 'earning_component', 0);
            $deductionComponent = Arr::get($data, 'deduction_component', 0);
            $employeeContribution = Arr::get($data, 'employee_contribution', 0);
            $employerContribution = Arr::get($data, 'employer_contribution', 0);

            $netSalary = $earningComponent - $deductionComponent - $employeeContribution;

            $difference = [
                'earning_component' => $earningComponent - $salaryStructure->net_earning->value,
                'deduction_component' => $deductionComponent - $salaryStructure->net_deduction->value,
                'employee_contribution' => $employeeContribution - $salaryStructure->net_employee_contribution->value,
                'employer_contribution' => $employerContribution - $salaryStructure->net_employer_contribution->value,
                'net_salary' => $netSalary - $salaryStructure->net_salary->value,
            ];

            $difference = collect($difference)->filter(function ($value) {
                return $value != 0;
            })->toArray();

            if ($difference) {
                // logger()->info('Employee ID: ' . $salaryStructure->employee_id);
                // logger()->info($difference);

                $salaryStructure->update([
                    'net_earning' => $earningComponent,
                    'net_deduction' => $deductionComponent,
                    'net_employee_contribution' => $employeeContribution,
                    'net_employer_contribution' => $employerContribution,
                    'net_salary' => $netSalary,
                ]);
            }
        }

        \DB::commit();

        return [
            'status' => 'success',
            'message' => __('global.updated', ['attribute' => __('employee.payroll.salary_structure.salary_structure')]),
        ];
    }
}
