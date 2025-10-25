<?php

namespace App\Actions\Employee\Payroll;

use App\Enums\Employee\Attendance\Category as AttendanceCategory;
use App\Models\Employee\Attendance\Attendance;
use Illuminate\Support\Collection;

class CalculatePresent
{
    public function execute(Attendance $attendance, Collection $attendanceTypes): float
    {
        $present = $attendance?->L ?? 0;
        $halfDay = 0;

        $present += $attendance?->HDL ?? 0;

        foreach ($attendanceTypes->whereIn('category.value', [AttendanceCategory::PRESENT->value, AttendanceCategory::HOLIDAY->value]) as $attendanceType) {
            $attendanceCode = $attendanceType->code;
            $present += $attendance?->$attendanceCode ?? 0;
        }

        foreach ($attendanceTypes->whereIn('category.value', [AttendanceCategory::HALF_DAY->value]) as $attendanceType) {
            $attendanceCode = $attendanceType->code;
            $halfDay += ($attendance?->$attendanceCode ?? 0) + ($attendance?->HDs ?? 0);
        }

        $present += ($halfDay / 2);

        return $present;
    }
}
