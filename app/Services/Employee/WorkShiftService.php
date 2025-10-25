<?php

namespace App\Services\Employee;

use App\Models\Employee\Attendance\Timesheet;
use App\Models\Employee\Employee;
use App\Models\Employee\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkShiftService
{
    public function preRequisite(Request $request): array
    {
        return [];
    }

    public function findByUuidOrFail(Employee $employee, string $uuid): WorkShift
    {
        return WorkShift::query()
            ->whereEmployeeId($employee->id)
            ->whereUuid($uuid)
            ->getOrFail(trans('employee.attendance.work_shift.work_shift'));
    }

    public function create(Request $request, Employee $employee): WorkShift
    {
        \DB::beginTransaction();

        $workShift = WorkShift::forceCreate($this->formatParams($request, $employee));

        \DB::commit();

        return $workShift;
    }

    private function formatParams(Request $request, Employee $employee, ?WorkShift $workShift = null): array
    {
        $formatted = [
            'work_shift_id' => $request->work_shift_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ];

        if (! $workShift) {
            $formatted['employee_id'] = $employee->id;
        }

        return $formatted;
    }

    private function ensureTimesheetNotMarked(WorkShift $workShift): void
    {
        $TimesheetExists = Timesheet::query()
            ->whereEmployeeId($workShift->employee_id)
            ->whereWorkShiftId($workShift->work_shift_id)
            ->exists();

        if ($TimesheetExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('employee.attendance.work_shift.work_shift'), 'dependency' => trans('employee.attendance.timesheet.timesheet')])]);
        }
    }

    public function update(Request $request, Employee $employee, WorkShift $workShift): void
    {
        $this->ensureTimesheetNotMarked($workShift);

        \DB::beginTransaction();

        $workShift->forceFill($this->formatParams($request, $employee, $workShift))->save();

        \DB::commit();
    }

    public function deletable(Employee $employee, WorkShift $workShift): void
    {
        $this->ensureTimesheetNotMarked($workShift);
    }
}
