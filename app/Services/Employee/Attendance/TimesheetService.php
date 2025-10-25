<?php

namespace App\Services\Employee\Attendance;

use App\Models\Employee\Attendance\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimesheetService
{
    public function create(Request $request): Timesheet
    {
        $dateTimesheetExists = Timesheet::query()
            ->whereNotNull('status')
            ->where('employee_id', $request->employee_id)
            ->where('date', $request->date)
            ->exists();

        if ($dateTimesheetExists) {
            throw ValidationException::withMessages(['message' => trans('employee.attendance.timesheet.already_synched')]);
        }

        \DB::beginTransaction();

        $timesheet = Timesheet::forceCreate($this->formatParams($request));

        \DB::commit();

        return $timesheet;
    }

    private function formatParams(Request $request, ?Timesheet $timesheet = null): array
    {
        $formatted = [
            'employee_id' => $request->employee_id,
            'work_shift_id' => $request->work_shift_id,
            'date' => $request->date,
            'in_at' => $request->in_at,
            'out_at' => $request->out_at,
            'is_manual' => 1,
            'remarks' => $request->remarks,
        ];

        $meta = $timesheet?->meta ?? [];
        $meta['is_overnight'] = $request->is_overnight;
        $meta['is_holiday'] = $request->is_holiday;

        $formatted['meta'] = $meta;

        return $formatted;
    }

    private function ensureNotSynched(Timesheet $timesheet): void
    {
        if ($timesheet->status) {
            throw ValidationException::withMessages(['message' => trans('employee.attendance.timesheet.already_synched')]);
        }
    }

    public function update(Request $request, Timesheet $timesheet): void
    {
        $this->ensureNotSynched($timesheet);

        \DB::beginTransaction();

        $timesheet->forceFill($this->formatParams($request, $timesheet))->save();

        \DB::commit();
    }

    public function deletable(Timesheet $timesheet): void
    {
        $this->ensureNotSynched($timesheet);
    }
}
