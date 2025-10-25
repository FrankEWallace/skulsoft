<?php

namespace App\Actions\Employee\Payroll;

use App\Enums\Employee\Attendance\Category as AttendanceCategory;
use App\Models\Employee\Attendance\Attendance;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GetAttendanceSummary
{
    public function execute(Attendance $attendance, Collection $attendanceTypes): array
    {
        $attendances = [];

        if ($attendanceTypes->count() == 0) {
            throw ValidationException::withMessages(['attendance_types' => trans('global.could_not_find', ['attribute' => trans('employee.attendance.type.type')])]);
        }

        array_push($attendances, [
            'code' => 'L',
            'name' => trans('employee.leave.leave'),
            'count' => $attendance?->L ?? 0,
            'color' => 'danger',
            'unit' => 'days',
        ]);

        array_push($attendances, [
            'code' => 'HDL',
            'name' => trans('employee.leave.half_day_leave'),
            'count' => $attendance?->HDL ?? 0,
            'color' => 'info',
            'unit' => 'days',
        ]);

        if (! $attendanceTypes->firstWhere('code', 'LWP')) {
            array_push($attendances, [
                'code' => 'LWP',
                'name' => trans('employee.leave.leave_without_pay_short'),
                'count' => $attendance?->LWP ?? 0,
                'color' => 'warning',
                'unit' => 'days',
            ]);
        }

        foreach ($attendanceTypes as $attendanceType) {
            $attendanceCode = $attendanceType->code;

            $attendanceCount = $attendance?->$attendanceCode ?? 0;
            if ($attendanceType->code == 'LWP') {
                $attendanceCount = ($attendance?->LWP ?? 0) + ($attendance?->LWPs ?? 0);
            } elseif ($attendanceType->code == 'HD') {
                $attendanceCount = ($attendance?->HD ?? 0) + ($attendance?->HDs ?? 0);
            }

            array_push($attendances, [
                'code' => $attendanceType->code,
                'name' => $attendanceType->name,
                'count' => $attendanceCount,
                'color' => AttendanceCategory::getColor($attendanceType->category->value),
                'unit' => 'days',
            ]);
        }

        return $attendances;
    }
}
