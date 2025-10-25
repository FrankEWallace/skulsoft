<?php

namespace App\Http\Requests\Employee\Payroll;

use App\Enums\ComparisonOperator;
use App\Enums\Employee\Payroll\PayHeadCategory;
use App\Enums\Employee\Payroll\PayHeadType;
use App\Enums\Employee\Payroll\PayrollVariable;
use App\Enums\LogicalOperator;
use App\Models\Employee\Attendance\Type as AttendanceType;
use App\Models\Employee\Payroll\PayHead;
use App\Models\Employee\Payroll\SalaryTemplate;
use App\Support\Evaluator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class SalaryTemplateRequest extends FormRequest
{
    use Evaluator;

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
        $rules = [
            'name' => 'required|min:2|max:100',
            'alias' => 'nullable|min:2|max:100',
            'has_hourly_payroll' => 'boolean',
            'records' => 'array',
            'records.*.computation' => 'required_if:records.*.type,computation',
            'records.*.has_range' => 'boolean',
            'records.*.conditional_formulas' => 'required_if:records.*.has_condition,true|array',
            'records.*.conditional_formulas.*.formula' => 'required',
            'records.*.conditional_formulas.*.conditions' => 'array',
            'records.*.conditional_formulas.*.conditions.*.reference_pay_head' => ['required'],
            'records.*.conditional_formulas.*.conditions.*.value' => ['required', 'numeric'],
            'records.*.conditional_formulas.*.conditions.*.operator' => ['required', new Enum(ComparisonOperator::class)],
            'records.*.conditional_formulas.*.conditions.*.logical_operator' => ['required', new Enum(LogicalOperator::class)],
            'description' => 'nullable|min:2|max:1000',
        ];

        if (! $this->boolean('has_hourly_payroll')) {
            $rules['records'] = 'array|required|min:1';
            $rules['records.*.type'] = ['required', new Enum(PayHeadType::class)];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('salary_template.uuid');

            $existingNames = SalaryTemplate::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingNames) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('employee.payroll.salary_template.props.name')]));
            }

            $attendanceTypes = AttendanceType::query()
                ->byTeam()
                ->productionBased()
                ->get();

            $attendanceTypeUuids = $attendanceTypes->pluck('uuid')->all();

            $payHeads = PayHead::query()
                ->byTeam()
                ->select('id', 'uuid', 'code')
                ->get();

            $payHeadCodes = $payHeads->pluck('code')->all();
            $payHeadUuids = $payHeads->pluck('uuid')->all();

            if ($this->has_hourly_payroll) {
                $this->merge([
                    'records' => [],
                ]);
            }

            $newRecords = [];
            $notApplicableCodes = [];
            foreach ($this->records as $index => $record) {
                $recordUuid = Arr::get($record, 'pay_head.uuid');
                $code = Arr::get($record, 'pay_head.code');
                $category = Arr::get($record, 'pay_head.category.value');
                $type = Arr::get($record, 'type');
                $computation = Arr::get($record, 'computation');
                $attendanceType = Arr::get($record, 'attendance_type.uuid');
                $hasRange = Arr::get($record, 'has_range');
                $hasCondition = Arr::get($record, 'has_condition');
                $minValue = Arr::get($record, 'min_value');
                $maxValue = Arr::get($record, 'max_value');

                if ($type == 'not_applicable') {
                    $notApplicableCodes[] = $code;
                }

                if (! in_array($recordUuid, $payHeadUuids)) {
                    throw ValidationException::withMessages(['message' => trans('validation.exists', ['attribute' => trans('employee.payroll.pay_head.pay_head')])]);
                }

                if ($type == PayHeadType::COMPUTATION->value) {
                    if (Str::of($computation)->contains('#'.$code.'#')) {
                        $validator->errors()->add('records.'.$index.'.computation', trans('employee.payroll.salary_template.computation_contains_self_pay_head'));
                    }

                    foreach ($payHeadCodes as $payHeadCode) {
                        if (! in_array($payHeadCode, $notApplicableCodes)) {
                            $computation = str_replace('#'.$payHeadCode.'#', 1, $computation);
                        }
                    }

                    if ($hasRange) {
                        if (! is_numeric($minValue)) {
                            $validator->errors()->add('records.'.$index.'.min_value', trans('validation.numeric', ['attribute' => __('employee.payroll.salary_template.props.min_value')]));
                        }

                        if (! is_numeric($maxValue)) {
                            $validator->errors()->add('records.'.$index.'.max_value', trans('validation.numeric', ['attribute' => __('employee.payroll.salary_template.props.max_value')]));
                        }

                        if ($minValue < 0) {
                            $validator->errors()->add('records.'.$index.'.min_value', trans('validation.min.numeric', ['attribute' => __('employee.payroll.salary_template.props.min_value'), 'min' => 0]));
                        }

                        if ($maxValue < 0) {
                            $validator->errors()->add('records.'.$index.'.max_value', trans('validation.min.numeric', ['attribute' => __('employee.payroll.salary_template.props.max_value'), 'min' => 0]));
                        }

                        if ($minValue > $maxValue) {
                            $validator->errors()->add('records.'.$index.'.min_value', trans('validation.min.numeric', ['attribute' => __('employee.payroll.salary_template.props.min_value'), 'min' => $maxValue]));
                        }
                    }

                    foreach (PayrollVariable::getKeys() as $payrollVariable) {
                        $computation = str_replace('#'.PayrollVariable::tryFrom($payrollVariable)->name.'#', 1, $computation);
                    }

                    if ($hasCondition) {
                        $conditionalFormulas = Arr::get($record, 'conditional_formulas', []);

                        if (empty($conditionalFormulas)) {
                            $validator->errors()->add('records.'.$index.'.conditional_formulas', trans('validation.required', ['attribute' => __('employee.payroll.salary_template.props.condition')]));
                        }

                        foreach ($conditionalFormulas as $conditionalFormulaIndex => $conditionalFormula) {
                            $conditions = Arr::get($conditionalFormula, 'conditions', []);
                            $formula = Arr::get($conditionalFormula, 'formula');

                            if (Str::of($formula)->contains('#'.$code.'#')) {
                                $validator->errors()->add('records.'.$index.'.conditional_formulas.'.$conditionalFormulaIndex.'.formula', trans('employee.payroll.salary_template.computation_contains_self_pay_head'));
                            }

                            foreach ($conditions as $conditionIndex => $condition) {
                                $operator = Arr::get($condition, 'operator');
                                $value = Arr::get($condition, 'value');
                                $referencePayHead = Arr::get($condition, 'reference_pay_head.value');
                                $logicalOperator = Arr::get($condition, 'logical_operator');

                                if ($referencePayHead == $code) {
                                    $validator->errors()->add('records.'.$index.'.conditional_formulas.'.$conditionalFormulaIndex.'.conditions.'.$conditionIndex.'.reference_pay_head', trans('validation.exists', ['attribute' => __('employee.payroll.salary_template.props.reference_pay_head')]));
                                }
                            }

                            // earning category check

                            if ($category == PayHeadCategory::EARNING->value && ($referencePayHead == 'GROSS_EARNING' || $referencePayHead == 'EARNING_COMPONENT')) {
                                $validator->errors()->add('records.'.$index.'.conditional_formulas.'.$conditionalFormulaIndex.'.conditions.'.$conditionIndex.'.reference_pay_head', trans('employee.payroll.salary_template.earning_component_cannot_be_referenced_in_earning_pay_head'));
                            }

                            if ($category == PayHeadCategory::EARNING->value && (Str::of($formula)->contains('#GROSS_EARNING#') || Str::of($formula)->contains('#EARNING_COMPONENT#'))) {
                                $validator->errors()->add('records.'.$index.'.conditional_formulas.'.$conditionalFormulaIndex.'.formula', trans('employee.payroll.salary_template.earning_component_cannot_be_referenced_in_earning_pay_head'));
                            }

                            if ($category == PayHeadCategory::EARNING->value && ($referencePayHead == 'GROSS_DEDUCTION' || $referencePayHead == 'DEDUCTION_COMPONENT')) {
                                $validator->errors()->add('records.'.$index.'.conditional_formulas.'.$conditionalFormulaIndex.'.conditions.'.$conditionIndex.'.reference_pay_head', trans('employee.payroll.salary_template.deduction_component_cannot_be_referenced_in_earning_pay_head'));
                            }

                            if ($category == PayHeadCategory::EARNING->value && (Str::of($formula)->contains('#GROSS_DEDUCTION#') || Str::of($formula)->contains('#DEDUCTION_COMPONENT#'))) {
                                $validator->errors()->add('records.'.$index.'.conditional_formulas.'.$conditionalFormulaIndex.'.formula', trans('employee.payroll.salary_template.deduction_component_cannot_be_referenced_in_earning_pay_head'));
                            }

                            // deduction category check

                            if ($category == PayHeadCategory::DEDUCTION->value && ($referencePayHead == 'GROSS_DEDUCTION' || $referencePayHead == 'DEDUCTION_COMPONENT')) {
                                $validator->errors()->add('records.'.$index.'.conditional_formulas.'.$conditionalFormulaIndex.'.conditions.'.$conditionIndex.'.reference_pay_head', trans('employee.payroll.salary_template.deduction_component_cannot_be_referenced_in_deduction_pay_head'));
                            }

                            if ($category == PayHeadCategory::DEDUCTION->value && (Str::of($formula)->contains('#GROSS_DEDUCTION#') || Str::of($formula)->contains('#DEDUCTION_COMPONENT#'))) {
                                $validator->errors()->add('records.'.$index.'.conditional_formulas.'.$conditionalFormulaIndex.'.formula', trans('employee.payroll.salary_template.deduction_component_cannot_be_referenced_in_deduction_pay_head'));
                            }

                            $evaluationFormulat = preg_replace('/#([A-Z_]+)#/', '1', $formula);

                            if ($this->evaluate($evaluationFormulat) === 'invalid') {
                                $validator->errors()->add('records.'.$index.'.conditional_formulas.'.$conditionalFormulaIndex.'.formula', trans('validation.exists', ['attribute' => __('employee.payroll.salary_template.props.conditional_value')]));
                            }
                        }
                    }

                    if ($this->evaluate($computation) === 'invalid') {
                        $validator->errors()->add('records.'.$index.'.computation', trans('validation.exists', ['attribute' => __('employee.payroll.salary_template.props.computation')]));
                    }
                } elseif ($type == PayHeadType::PRODUCTION_BASED->value) {
                    if (! in_array($attendanceType, $attendanceTypeUuids)) {
                        $validator->errors()->add('records.'.$index.'.attendance_type', trans('validation.exists', ['attribute' => __('employee.attendance.type.type')]));
                    }
                }

                $record['attendance_type_id'] = $attendanceTypes->firstWhere('uuid', $attendanceType)?->id;

                $newRecords[] = Arr::add($record, 'pay_head.id', $payHeads->firstWhere('uuid', $recordUuid)->id);
            }

            $this->merge(['records' => $newRecords]);

            if (! $this->alias) {
                return;
            }

            $existingAliases = SalaryTemplate::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereAlias($this->alias)
                ->exists();

            if ($existingAliases) {
                $validator->errors()->add('alias', trans('validation.unique', ['attribute' => __('employee.payroll.salary_template.props.alias')]));
            }
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
            'name' => __('employee.payroll.salary_template.props.name'),
            'alias' => __('employee.payroll.salary_template.props.alias'),
            'records.*.type' => __('employee.payroll.salary_template.props.type'),
            'records.*.has_range' => __('employee.payroll.salary_template.props.has_range'),
            'records.*.min_value' => __('employee.payroll.salary_template.props.min_value'),
            'records.*.max_value' => __('employee.payroll.salary_template.props.max_value'),
            'records.*.conditional_formulas.*.formula' => __('employee.payroll.salary_template.props.conditional_value'),
            'records.*.conditional_formulas.*.conditions.*.reference_pay_head' => __('employee.payroll.salary_template.props.reference_pay_head'),
            'records.*.conditional_formulas.*.conditions.*.value' => __('employee.payroll.salary_template.props.value'),
            'records.*.conditional_formulas.*.conditions.*.operator' => __('employee.payroll.salary_template.props.operator'),
            'records.*.conditional_formulas.*.conditions.*.logical_operator' => __('employee.payroll.salary_template.props.logical_operator'),
            'description' => __('employee.payroll.salary_template.props.description'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'records.*.computation.required_if' => __('validation.required', ['attribute' => __('employee.payroll.salary_template.props.computation')]),
            'records.*.min_value.required_if' => __('validation.required', ['attribute' => __('employee.payroll.salary_template.props.min_value')]),
            'records.*.max_value.required_if' => __('validation.required', ['attribute' => __('employee.payroll.salary_template.props.max_value')]),
            'records.*.conditional_formulas.required_if' => __('validation.required', ['attribute' => __('employee.payroll.salary_template.props.conditional_value')]),
        ];
    }
}
