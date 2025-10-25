<?php

namespace App\Services\Employee\Payroll;

use App\Enums\ComparisonOperator;
use App\Enums\Employee\Payroll\PayHeadType;
use App\Enums\Employee\Payroll\PayrollVariable;
use App\Enums\LogicalOperator;
use App\Http\Resources\Employee\Attendance\TypeResource as AttendanceTypeResource;
use App\Http\Resources\Employee\Payroll\PayHeadResource;
use App\Models\Employee\Attendance\Type as AttendanceType;
use App\Models\Employee\Payroll\PayHead;
use App\Models\Employee\Payroll\SalaryStructure;
use App\Models\Employee\Payroll\SalaryTemplate;
use App\Models\Employee\Payroll\SalaryTemplateRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalaryTemplateService
{
    public function preRequisite(Request $request): array
    {
        $payHeadTypes = PayHeadType::getOptions();

        $payHeads = PayHead::query()
            ->byTeam()
            ->orderBy('position', 'asc')
            ->get();

        $payrollVariables = collect(PayrollVariable::getKeys())
            ->map(function ($key) {
                return [
                    'label' => trans(PayrollVariable::translation().$key),
                    'value' => PayrollVariable::from($key)->name,
                ];
            });

        $variables = $payHeads->map(function ($payHead) {
            return [
                'label' => $payHead->name,
                'value' => $payHead->code,
            ];
        });

        $variables = $variables->merge($payrollVariables);

        $comparisonOperators = ComparisonOperator::getOptions();

        $logicalOperators = LogicalOperator::getOptions();

        $payHeads = PayHeadResource::collection($payHeads);

        $attendanceTypes = AttendanceTypeResource::collection(AttendanceType::byTeam()->productionBased()->get());

        return compact('payHeadTypes', 'payHeads', 'attendanceTypes', 'variables', 'comparisonOperators', 'logicalOperators');
    }

    public function create(Request $request): SalaryTemplate
    {
        \DB::beginTransaction();

        $salaryTemplate = SalaryTemplate::forceCreate($this->formatParams($request));

        if (! $request->boolean('has_hourly_payroll')) {
            $this->updateRecords($request, $salaryTemplate);
        }

        \DB::commit();

        return $salaryTemplate;
    }

    private function formatParams(Request $request, ?SalaryTemplate $salaryTemplate = null): array
    {
        $formatted = [
            'name' => $request->name,
            'alias' => $request->alias,
            'description' => $request->description,
        ];

        if (! $salaryTemplate) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        $config = $salaryTemplate?->config ?? [];
        $config['has_hourly_payroll'] = $request->boolean('has_hourly_payroll');

        $formatted['config'] = $config;

        return $formatted;
    }

    private function updateRecords(Request $request, SalaryTemplate $salaryTemplate): void
    {
        $payHeadIds = [];
        foreach ($request->records as $index => $record) {
            $payHeadId = Arr::get($record, 'pay_head.id');
            $type = Arr::get($record, 'type');

            $salaryTemplateRecord = SalaryTemplateRecord::firstOrCreate([
                'salary_template_id' => $salaryTemplate->id,
                'pay_head_id' => $payHeadId,
            ]);

            $salaryTemplateRecord->uuid = $salaryTemplateRecord->uuid ?? (string) Str::uuid();
            $salaryTemplateRecord->position = $index;
            $salaryTemplateRecord->type = $type;
            $salaryTemplateRecord->computation = $type == PayHeadType::COMPUTATION->value ? Arr::get($record, 'computation') : null;
            $salaryTemplateRecord->attendance_type_id = Arr::get($record, 'attendance_type_id');

            $meta = $salaryTemplateRecord->meta ?? [];
            if ($type == PayHeadType::COMPUTATION->value && Arr::get($record, 'has_range')) {
                $meta['has_range'] = true;
                $meta['min_value'] = round(Arr::get($record, 'min_value'), 2);
                $meta['max_value'] = round(Arr::get($record, 'max_value'), 2);
            } else {
                $meta['has_range'] = false;
                $meta = Arr::except($meta, ['min_value', 'max_value']);
            }

            $meta['as_total'] = Arr::get($record, 'as_total', false);

            if (Arr::get($record, 'has_condition')) {
                $meta['has_condition'] = true;
                $meta['conditional_formulas'] = Arr::get($record, 'conditional_formulas', []);
            } else {
                $meta['has_condition'] = false;
                $meta['conditional_formulas'] = [];
            }

            $salaryTemplateRecord->meta = $meta;
            $salaryTemplateRecord->save();

            $payHeadIds[] = $payHeadId;
        }

        SalaryTemplateRecord::whereSalaryTemplateId($salaryTemplate->id)->whereNotIn('pay_head_id', $payHeadIds)->delete();
    }

    private function ensureDoesntHaveSalaryStructure(SalaryTemplate $salaryTemplate): void
    {
        $salaryStructureExists = SalaryStructure::whereSalaryTemplateId($salaryTemplate->id)->exists();

        if ($salaryStructureExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('employee.payroll.salary_template.salary_template'), 'dependency' => trans('employee.payroll.salary_structure.salary_structure')])]);
        }
    }

    public function update(Request $request, SalaryTemplate $salaryTemplate): void
    {
        $this->ensureDoesntHaveSalaryStructure($salaryTemplate);

        \DB::beginTransaction();

        $salaryTemplate->forceFill($this->formatParams($request, $salaryTemplate))->save();

        if (! $request->boolean('has_hourly_payroll')) {
            $this->updateRecords($request, $salaryTemplate);
        } else {
            SalaryTemplateRecord::whereSalaryTemplateId($salaryTemplate->id)->delete();
        }

        \DB::commit();
    }

    public function deletable(SalaryTemplate $salaryTemplate): void
    {
        $this->ensureDoesntHaveSalaryStructure($salaryTemplate);
    }
}
