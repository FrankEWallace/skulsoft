<?php

namespace App\Services\Employee\Attendance;

use App\Enums\Day;
use App\Models\Employee\Attendance\WorkShift;
use App\Models\Employee\WorkShift as EmployeeWorkShift;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkShiftService
{
    public function preRequisite(Request $request): array
    {
        $days = Day::getOptions();

        return compact('days');
    }

    public function create(Request $request): WorkShift
    {
        \DB::beginTransaction();

        $workShift = WorkShift::forceCreate($this->formatParams($request));

        \DB::commit();

        return $workShift;
    }

    private function formatParams(Request $request, ?WorkShift $workShift = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'records' => $request->records,
        ];

        if (! $workShift) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    private function ensureNotAssigned(WorkShift $workShift): void
    {
        $employeeWorkShiftExists = EmployeeWorkShift::whereWorkShiftId($workShift->id)->exists();

        if ($employeeWorkShiftExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('employee.attendance.work_shift.work_shift'), 'dependency' => trans('employee.employee')])]);
        }
    }

    public function update(Request $request, WorkShift $workShift): void
    {
        $this->ensureNotAssigned($workShift);

        \DB::beginTransaction();

        $workShift->forceFill($this->formatParams($request, $workShift))->save();

        \DB::commit();
    }

    public function deletable(WorkShift $workShift): void
    {
        $this->ensureNotAssigned($workShift);
    }
}
