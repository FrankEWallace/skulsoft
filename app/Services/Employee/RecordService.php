<?php

namespace App\Services\Employee;

use App\Models\Employee\Employee;
use App\Models\Employee\Record;
use App\Models\Incharge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RecordService
{
    public function preRequisite(Request $request): array
    {
        return [];
    }

    public function findByUuidOrFail(Employee $employee, ?string $uuid = null): Record
    {
        return Record::withDetail()->where('employee_records.uuid', $uuid)->whereEmployeeId($employee->id)->getOrFail(trans('employee.record.record'));
    }

    private function getLastRecord(Employee $employee): Record
    {
        $lastRecord = Record::whereEmployeeId($employee->id)->whereId($employee->last_record_id)->first();

        if (! $lastRecord) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        return $lastRecord;
    }

    private function ensureRecordNotEnded(Record $record)
    {
        if ($record->end_date->value) {
            throw ValidationException::withMessages(['message' => trans('employee.record.could_not_perform_if_employment_ended')]);
        }
    }

    private function getPreviousRecord(Employee $employee, Record $record, $failed = true): ?Record
    {
        $previousRecord = Record::whereEmployeeId($employee->id)->where('id', '<', $record->id)->where('end_date', '=', Carbon::parse($record->start_date->value)->subDay(1)->toDateString())->orderBy('start_date', 'desc')->first();

        if (! $failed) {
            return $previousRecord;
        }

        if (! $previousRecord) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        return $previousRecord;
    }

    private function ensureLastRecord(Employee $employee, Record $record)
    {
        $ensureLastRecord = Record::whereEmployeeId($employee->id)->where('start_date', '>', $record->start_date->value)->doesntExist();

        if (! $ensureLastRecord) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_modify_past', ['attribute' => trans('employee.record.record')])]);
        }
    }

    private function ensureHasMultipleRecord(Employee $employee): void
    {
        $employee->load('records');

        if ($employee->records()->count() === 1) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_delete_default', ['attribute' => trans('employee.record.record')])]);
        }
    }

    public function create(Request $request, Employee $employee): Record
    {
        \DB::beginTransaction();

        $record = $this->getLastRecord($employee);

        if ($record->start_date->value == $request->start_date) {
            throw ValidationException::withMessages(['message' => trans('employee.record.start_date_is_same_as_previous_start_date')]);
        }

        // Important: employee cannot be reemployed if employment is ended, tried and tested
        $this->ensureRecordNotEnded($record);

        $this->ensureLastRecord($employee, $record);

        if ($record->start_date->value > $request->start_date) {
            throw ValidationException::withMessages(['start_date' => trans('employee.record.start_date_lt_previous_start_date')]);
        }

        if (
            $record->department_id == $request->department_id &&
            $record->designation_id == $request->designation_id &&
            $record->employment_status_id == $request->employment_status_id
        ) {
            throw ValidationException::withMessages(['message' => trans('employee.record.could_not_perform_if_no_change')]);
        }

        $record->end_date = Carbon::parse($request->start_date)->subDay(1)->toDateString();
        $record->save();

        $record = Record::forceCreate($this->formatParams($request, $employee));

        $record->addMedia($request);

        \DB::commit();

        return $record;
    }

    private function formatParams(Request $request, Employee $employee, ?Record $record = null): array
    {
        $formatted = [
            'start_date' => $request->start_date,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'employment_status_id' => $request->employment_status_id,
            'remarks' => $request->remarks,
        ];

        if (! $record) {
            $formatted['employee_id'] = $employee->id;
        }

        return $formatted;
    }

    public function update(Request $request, Employee $employee, Record $record): void
    {
        \DB::beginTransaction();

        $this->ensureLastRecord($employee, $record);

        $previousRecord = $this->getPreviousRecord($employee, $record, false);

        if ($request->end && $record->start_date->value > $request->end_date) {
            throw ValidationException::withMessages(['end_date' => trans('employee.record.end_date_lt_start_date')]);
        }

        if ($previousRecord && $previousRecord->start_date->value > $request->start_date) {
            throw ValidationException::withMessages(['start_date' => trans('employee.record.start_date_lt_previous_start_date')]);
        }

        if ($record->is_ended && ! $request->end) {
            $record->is_ended = false;
            $record->end_date = null;
            $employee->leaving_date = null;
        } elseif (! $record->is_ended && $request->end) {
            $record->is_ended = true;
            $record->end_date = $request->end_date;
            $employee->leaving_date = $request->end_date;

            $this->inactiveAllInchargeTenure($employee, $record);
        }

        $record->save();

        if ($previousRecord) {
            $previousRecord->end_date = Carbon::parse($request->start_date)->subDay(1)->toDateString();
            $previousRecord->save();
        } else {
            $employee->joining_date = $request->start_date;
        }

        $employee->save();

        $record->forceFill($this->formatParams($request, $employee, $record))->save();

        $record->updateMedia($request);

        \DB::commit();
    }

    private function inactiveAllInchargeTenure(Employee $employee, Record $record): void
    {
        Incharge::query()
            ->whereEmployeeId($employee->id)
            ->whereNull('end_date')
            ->update([
                'end_date' => $record->end_date->value,
            ]);
    }

    public function deletable(Employee $employee, Record $record): void
    {
        $this->ensureHasMultipleRecord($employee);

        $this->ensureLastRecord($employee, $record);
    }

    public function delete(Employee $employee, Record $record): void
    {
        \DB::beginTransaction();

        $previousRecord = $this->getPreviousRecord($employee, $record);

        $previousRecord->end_date = null;
        $previousRecord->is_ended = false;
        $previousRecord->save();

        if ($record->is_ended) {
            $employee->leaving_date = null;
            $employee->save();
        }

        $record->delete();

        \DB::commit();
    }
}
