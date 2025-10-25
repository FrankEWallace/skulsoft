<?php

namespace App\Services\Employee\Payroll;

use App\Actions\Employee\Payroll\FetchPayrollRecord;
use App\Actions\Employee\Payroll\UpdatePayrollRecord;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Models\Employee\Payroll\Payroll;
use App\Support\Evaluator;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    use Evaluator, FormatCodeNumber;

    private function codeNumber(Request $request)
    {
        $numberPrefix = config('config.employee.payroll_number_prefix');
        $numberSuffix = config('config.employee.payroll_number_suffix');
        $digit = config('config.employee.payroll_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) Payroll::whereHas('employee', function ($q) {
            $q->byTeam();
        })->whereNumberFormat($numberFormat)->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    private function validateCodeNumber(Request $request, ?string $uuid = null): array
    {
        if (! $request->code_number) {
            return $this->codeNumber($request);
        }

        $duplicateCodeNumber = Payroll::whereHas('employee', function ($q) {
            $q->byTeam();
        })->whereCodeNumber($request->code_number)->when($uuid, function ($q, $uuid) {
            $q->where('uuid', '!=', $uuid);
        })->count();

        if ($duplicateCodeNumber) {
            throw ValidationException::withMessages(['message' => trans('global.duplicate', ['attribute' => trans('employee.payroll.config.props.code_number')])]);
        }

        return [];
    }

    public function preRequisite(Request $request): array
    {
        $statuses = PayrollStatus::getOptions();

        return compact('statuses');
    }

    public function fetch(Request $request): array
    {
        return (new FetchPayrollRecord)->execute([
            'employee_id' => $request->employee->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'salary_structure' => $request->salary_structure,
            'attendance_types' => $request->attendance_types,
            'production_attendance_types' => $request->production_attendance_types,
            'ignore_attendance' => $request->boolean('ignore_attendance'),
        ]);
    }

    public function create(Request $request): Payroll
    {
        \DB::beginTransaction();

        $payroll = Payroll::forceCreate($this->formatParams($request));

        $this->updateRecords($request, $payroll);

        $payroll->status = PayrollStatus::PROCESSED->value;
        $payroll->save();

        \DB::commit();

        return $payroll;
    }

    private function formatParams(Request $request, ?Payroll $payroll = null): array
    {
        $codeNumberDetail = $this->validateCodeNumber($request, $payroll?->uuid);

        $formatted = [
            'remarks' => $request->remarks,
        ];

        if (! $payroll) {
            $formatted['employee_id'] = $request->employee->id;
            $formatted['salary_structure_id'] = $request->salary_structure->id;
            $formatted['start_date'] = $request->start_date;
            $formatted['end_date'] = $request->end_date;
            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number', $request->code_number);

            $meta['batch_uuid'] = (string) Str::uuid();
            $meta['team_id'] = auth()->user()->current_team_id;
            $meta['ignore_attendance'] = $request->boolean('ignore_attendance');
            $formatted['meta'] = $meta;
        }

        return $formatted;
    }

    private function updateRecords(Request $request, Payroll $payroll): void
    {
        $actual = [
            'earning' => $request->earning,
            'deduction' => $request->deduction,
            'employee_contribution' => $request->employee_contribution,
            'employer_contribution' => $request->employer_contribution,
            'salary' => $request->total,
        ];

        (new UpdatePayrollRecord)->execute($payroll, [
            'actual' => $actual,
            'records' => $request->records,
            'employee_id' => $request->employee->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'salary_structure' => $request->salary_structure,
            'attendance_types' => $request->attendance_types,
            'production_attendance_types' => $request->production_attendance_types,
            'has_hourly_payroll' => $request->boolean('has_hourly_payroll'),
            'ignore_attendance' => $request->boolean('ignore_attendance'),
        ]);
    }

    private function ensureIsLastPayroll(Payroll $payroll): void
    {
        $isLastPayroll = Payroll::whereEmployeeId($payroll->employee_id)->where('start_date', '>', $payroll->start_date->value)->doesntExist();

        if (! $isLastPayroll) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_modify_past_record', ['attribute' => trans('employee.payroll.payroll')])]);
        }
    }

    public function isProcessed(Payroll $payroll): void
    {
        if ($payroll->status != PayrollStatus::PROCESSED) {
            throw ValidationException::withMessages(['message' => trans('employee.payroll.not_processed')]);
        }
    }

    public function update(Request $request, Payroll $payroll): void
    {
        $this->ensureIsLastPayroll($payroll);

        \DB::beginTransaction();

        $payroll->forceFill($this->formatParams($request, $payroll))->save();

        $this->updateRecords($request, $payroll);

        \DB::commit();
    }

    public function deletable(Payroll $payroll): void
    {
        $this->ensureIsLastPayroll($payroll);
    }

    public function getBulkExport(Request $request): void {}
}
