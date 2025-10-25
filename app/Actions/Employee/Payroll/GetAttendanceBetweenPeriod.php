<?php

namespace App\Actions\Employee\Payroll;

use App\Helpers\CalHelper;
use App\Models\Employee\Attendance\Attendance;
use App\Models\Employee\Employee;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GetAttendanceBetweenPeriod
{
    public function execute(int $employeeId, string $startDate, string $endDate, Collection $attendanceTypes, array $params = []): Attendance
    {
        $employee = Employee::query()
            ->where('id', $employeeId)
            ->first();

        if ($employee->joining_date->value > $startDate) {
            $startDate = $employee->joining_date->value;
        }

        if ($employee->leaving_date->value && $employee->leaving_date->value < $endDate) {
            $endDate = $employee->leaving_date->value;
        }

        $query = Attendance::query()
            ->select('employee_id')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate]);

        foreach ($attendanceTypes as $attendanceType) {
            $query->selectRaw('count(case when attendance_type_id = '.$attendanceType->id.' and attendance_symbol IS NULL then 1 end) as '.$attendanceType->code);
        }

        $attendance = $query
            ->selectRaw("count(case when attendance_symbol = 'L' then 1 end) as L")
            ->selectRaw("count(case when attendance_symbol = 'HDL' then 1 end) as HDL")
            ->selectRaw("count(case when attendance_symbol = 'LWP' then 1 end) as LWPs")
            ->selectRaw("count(case when attendance_symbol = 'HD' then 1 end) as HDs")
            ->groupBy('employee_id')
            ->first();

        if (Arr::get($params, 'ignore_attendance', false)) {
            $attendance = new Attendance;
            $attendance->employee_id = $employeeId;
            $attendance->P = CalHelper::dateDiff($startDate, $endDate);
        } else {
            if (! $attendance) {
                throw ValidationException::withMessages(['start_date' => trans('employee.attendance.not_marked')]);
            }
        }

        return $attendance;
    }
}
