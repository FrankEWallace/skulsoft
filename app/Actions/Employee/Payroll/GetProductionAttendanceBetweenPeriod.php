<?php

namespace App\Actions\Employee\Payroll;

use App\Models\Employee\Attendance\Record as AttendanceRecord;
use Illuminate\Support\Collection;

class GetProductionAttendanceBetweenPeriod
{
    public function execute(int $employeeId, string $startDate, string $endDate): Collection
    {
        return AttendanceRecord::query()
            ->whereHas('attendance', function ($q) use ($employeeId, $startDate, $endDate) {
                $q->whereEmployeeId($employeeId)
                    ->whereBetween('date', [$startDate, $endDate]);
            })->get();
    }
}
