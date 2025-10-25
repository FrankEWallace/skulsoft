<?php

namespace App\Services\Employee\Payroll;

use App\Actions\Employee\Payroll\CalculatePayHeads;
use App\Enums\Employee\Attendance\ProductionUnit as AttendanceProductionUnit;
use App\Enums\Employee\Payroll\PayHeadCategory;
use App\Enums\Employee\Payroll\PayHeadType;
use App\Enums\Employee\Payroll\SalaryStructureUnit;
use App\Http\Resources\Employee\Payroll\SalaryTemplateResource;
use App\Models\Employee\Payroll\Payroll;
use App\Models\Employee\Payroll\SalaryStructure;
use App\Models\Employee\Payroll\SalaryStructureRecord;
use App\Models\Employee\Payroll\SalaryTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalaryStructureService
{
    public function preRequisite(Request $request): array
    {
        $salaryTemplates = SalaryTemplateResource::collection(SalaryTemplate::query()
            ->with('records.payHead')
            ->byTeam()
            ->get());

        return compact('salaryTemplates');
    }

    public function create(Request $request): SalaryStructure
    {
        \DB::beginTransaction();

        $salaryStructure = SalaryStructure::forceCreate($this->formatParams($request));

        if (! $request->boolean('has_hourly_payroll')) {
            $this->updateRecords($request, $salaryStructure);
        }

        \DB::commit();

        return $salaryStructure;
    }

    private function formatParams(Request $request, ?SalaryStructure $salaryStructure = null): array
    {
        $formatted = [
            'employee_id' => $request->employee_id,
            'salary_template_id' => $request->salary_template_id,
            'effective_date' => $request->effective_date,
            'hourly_pay' => $request->hourly_pay,
            'net_earning' => $request->net_earning,
            'net_deduction' => $request->net_deduction,
            'net_employee_contribution' => $request->net_employee_contribution,
            'net_employer_contribution' => $request->net_employer_contribution,
            'net_salary' => $request->net_salary,
            'description' => $request->description,
        ];

        if (! $salaryStructure) {
        }

        return $formatted;
    }

    public function getRecords(SalaryStructure $salaryStructure): array
    {
        $salaryTemplate = $salaryStructure->template;

        $records = [];

        foreach ($salaryStructure->records as $record) {
            $payHead = $salaryTemplate->records->firstWhere('pay_head_id', $record->pay_head_id)?->payHead;
            $records[] = [
                'pay_head' => $payHead?->uuid,
                'amount' => $record->amount->value,
            ];
        }

        $params = [
            'monthly_days' => 30,
            'working_days' => 30,
        ];

        $data = (new CalculatePayHeads)->execute($salaryTemplate, $records, $params);

        $payHeads = collect(Arr::get($data, 'pay_heads', []));

        $newRecords = [];
        foreach ($salaryTemplate->records as $record) {
            $payHead = $record->payHead;
            $payHeadData = $payHeads->firstWhere('code', $payHead->code);

            $newRecords[] = [
                'uuid' => $record->uuid,
                'pay_head' => [
                    'uuid' => $payHead->uuid,
                    'name' => $payHead->name,
                    'code' => $payHead->code,
                    'visibility' => $record->visibility,
                    'category' => PayHeadCategory::getDetail($payHead->category),
                ],
                'unit' => $record->type == PayHeadType::PRODUCTION_BASED ? AttendanceProductionUnit::getDetail('hourly') : SalaryStructureUnit::getDetail('monthly'),
                'enable_user_input' => $record->enable_user_input,
                'type' => PayHeadType::getDetail($record->type),
                'computation' => Arr::get($payHeadData, 'formula'),
                'amount' => \Price::from(Arr::get($payHeadData, 'amount', 0)),
            ];
        }

        return $newRecords;
    }

    private function updateRecords(Request $request, SalaryStructure $salaryStructure): void
    {
        $payHeadIds = [];
        foreach ($request->records as $index => $record) {
            $payHeadId = Arr::get($record, 'id');
            $amount = Arr::get($record, 'amount', 0);

            $salaryStructureRecord = SalaryStructureRecord::firstOrCreate([
                'salary_structure_id' => $salaryStructure->id,
                'pay_head_id' => $payHeadId,
            ]);

            $salaryStructureRecord->uuid = $salaryStructureRecord->uuid ?? (string) Str::uuid();
            $salaryStructureRecord->amount = $amount;
            $salaryStructureRecord->unit = Arr::get($record, 'type') == PayHeadType::PRODUCTION_BASED->value ? SalaryStructureUnit::HOURLY->value : SalaryStructureUnit::MONTHLY->value;
            $salaryStructureRecord->save();

            $payHeadIds[] = $payHeadId;
        }

        SalaryStructureRecord::whereSalaryStructureId($salaryStructure->id)->whereNotIn('pay_head_id', $payHeadIds)->delete();
    }

    private function ensureDoesntHavePayroll(SalaryStructure $salaryStructure): void
    {
        $payrollExists = Payroll::whereSalaryStructureId($salaryStructure->id)->exists();

        if ($payrollExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('employee.payroll.salary_structure.salary_structure'), 'dependency' => trans('employee.payroll.payroll')])]);
        }
    }

    public function update(Request $request, SalaryStructure $salaryStructure): void
    {
        $this->ensureDoesntHavePayroll($salaryStructure);

        \DB::beginTransaction();

        $salaryStructure->forceFill($this->formatParams($request, $salaryStructure))->save();

        if (! $request->boolean('has_hourly_payroll')) {
            $this->updateRecords($request, $salaryStructure);
        } else {
            $salaryStructure->records()->delete();
        }

        \DB::commit();
    }

    public function deletable(SalaryStructure $salaryStructure): void
    {
        $this->ensureDoesntHavePayroll($salaryStructure);
    }
}
