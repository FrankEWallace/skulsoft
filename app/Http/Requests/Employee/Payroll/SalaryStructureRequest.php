<?php

namespace App\Http\Requests\Employee\Payroll;

use App\Actions\Employee\Payroll\CalculatePayHeads;
use App\Concerns\SubordinateAccess;
use App\Enums\Employee\Payroll\PayHeadType;
use App\Models\Employee\Employee;
use App\Models\Employee\Payroll\Payroll;
use App\Models\Employee\Payroll\SalaryStructure;
use App\Models\Employee\Payroll\SalaryTemplate;
use App\Support\Evaluator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SalaryStructureRequest extends FormRequest
{
    use Evaluator, SubordinateAccess;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee' => 'required|uuid',
            'salary_template' => 'required|uuid',
            'hourly_pay' => 'numeric|min:0',
            'effective_date' => 'required|date_format:Y-m-d',
            'records' => 'array',
            'records.*.amount' => 'required|numeric|min:0',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('salary_structure');

            $employee = Employee::query()
                ->summary()
                ->filterAccessible()
                ->where('employees.uuid', $this->employee)
                ->getOrFail(trans('employee.employee'), 'employee');

            // let them add salary structure before joining date so that payroll can be generated from month to month
            // $this->validateEmployeeJoiningDate($employee, $this->effective_date, trans('employee.payroll.salary_structure.props.effective_date'), 'effective_date');

            // $this->validateEmployeeLeavingDate($employee, $this->effective_date, trans('employee.payroll.salary_structure.props.effective_date'), 'effective_date');

            $salaryTemplate = SalaryTemplate::query()
                ->byTeam()
                ->with('records.payHead')
                ->whereUuid($this->salary_template)
                ->getOrFail('employee.payroll.salary_template.salary_template', 'salary_template');

            $backwardSalaryStructure = SalaryStructure::query()
                ->whereEmployeeId($employee->id)
                ->where('effective_date', '>=', $this->effective_date)
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->exists();

            if ($backwardSalaryStructure) {
                $validator->errors()->add('employee', trans('employee.payroll.salary_structure.could_not_perform_if_defined_for_later_date'));
            }

            if ($this->method() == 'PATCH') {
                $payrollExistsForSalaryStructure = Payroll::query()
                    ->whereEmployeeId($employee->id)
                    ->whereHas('salaryStructure', function ($q) use ($uuid) {
                        $q->where('uuid', $uuid);
                    })
                    ->exists();

                if ($payrollExistsForSalaryStructure) {
                    $validator->errors()->add('effective_date', trans('employee.payroll.salary_structure.could_not_perform_if_payroll_generated_with_salary_structure'));
                }
            }

            $payrollExists = Payroll::query()
                ->whereEmployeeId($employee->id)
                ->where('start_date', '>=', $this->effective_date)
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->exists();

            if ($payrollExists) {
                $validator->errors()->add('effective_date', trans('employee.payroll.salary_structure.could_not_perform_if_payroll_generated'));
            }

            $this->merge([
                'employee_id' => $employee->id,
                'salary_template_id' => $salaryTemplate->id,
            ]);

            if ($salaryTemplate->has_hourly_payroll) {
                $this->merge([
                    'has_hourly_payroll' => true,
                    'records' => [],
                    'net_earning' => 0,
                    'net_deduction' => 0,
                    'net_employee_contribution' => 0,
                    'net_employer_contribution' => 0,
                    'net_salary' => 0,
                ]);

                return;
            }

            $records = collect($this->records)
                ->map(function ($record) {
                    return [
                        'pay_head' => Arr::get($record, 'pay_head.uuid'),
                        'amount' => Arr::get($record, 'amount', 0),
                    ];
                })
                ->toArray();

            $params = [
                'monthly_days' => 30,
                'working_days' => 30,
            ];

            $data = (new CalculatePayHeads)->execute($salaryTemplate, $records, $params);

            $earningComponent = Arr::get($data, 'earning_component', 0);
            $deductionComponent = Arr::get($data, 'deduction_component', 0);
            $employeeContribution = Arr::get($data, 'employee_contribution', 0);
            $employerContribution = Arr::get($data, 'employer_contribution', 0);

            $payHeads = Arr::get($data, 'pay_heads', []);

            $netSalary = $earningComponent - $deductionComponent - $employeeContribution;

            if ($netSalary < 0) {
                throw ValidationException::withMessages(['message' => trans('validation.min.numeric', ['attribute' => trans('employee.payroll.salary_structure.props.net_salary'), 'min' => 0])]);
            }

            $newRecords = [];
            foreach ($payHeads as $payHead) {
                if (Arr::get($payHead, 'id') && in_array(Arr::get($payHead, 'type'), PayHeadType::userInput())) {
                    $newAmount = Arr::get($payHead, 'amount', 0);

                    if (Arr::get($payHead, 'type') == PayHeadType::PRODUCTION_BASED->value) {
                        $newAmount = Arr::get(collect($records)->firstWhere('pay_head', Arr::get($payHead, 'uuid')), 'amount', 0);
                    }

                    $newRecords[] = [
                        'type' => Arr::get($payHead, 'type'),
                        'id' => $payHead['id'],
                        'amount' => $newAmount,
                    ];
                }
            }

            $this->merge([
                'has_hourly_payroll' => false,
                'records' => $newRecords,
                'net_earning' => $earningComponent,
                'net_deduction' => $deductionComponent,
                'net_employee_contribution' => $employeeContribution,
                'net_employer_contribution' => $employerContribution,
                'net_salary' => $netSalary,
            ]);
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'employee' => __('employee.employee'),
            'salary_template' => __('employee.payroll.salary_template.salary_template'),
            'effective_date' => __('employee.payroll.salary_structure.props.effective_date'),
            'records.*.amount' => __('employee.payroll.salary_structure.props.amount'),
            'description' => __('employee.payroll.salary_structure.props.description'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
