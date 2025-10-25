<?php

namespace App\Actions\Employee\Attendance;

use App\Helpers\CalHelper;
use App\Models\Employee\Attendance\Attendance;
use App\Models\Employee\Attendance\Timesheet;
use App\Models\Employee\Employee;
use App\Models\Employee\WorkShift as EmployeeWorkShift;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StoreTimesheet
{
    const CLOCK_IN = 'in';

    const CLOCK_OUT = 'out';

    const OVERNIGHT_THRESHOLD = '06:00:00';

    public function execute(Employee $employee, string $datetime, array $params = []): Timesheet
    {
        $date = Carbon::parse($datetime)->format('Y-m-d');
        $time = Carbon::parse($datetime)->format('H:i:s');
        $storeDatetime = CalHelper::storeDateTime($datetime);

        $employeeWorkShift = $this->findEmployeeWorkShift($employee, $date);

        $date = $this->checkDateForOvernightShift($employeeWorkShift, $datetime);

        $this->ensureAttendanceNotSynched($employee, $date);

        $timesheet = Timesheet::query()
            ->whereEmployeeId($employee->id)
            ->where('date', $date)
            ->orderBy('in_at', 'desc')
            ->first();

        $this->validateInput($timesheet, $params);

        $latitude = Arr::get($params, 'location.latitude');
        $longitude = Arr::get($params, 'location.longitude');
        $distance = Arr::get($params, 'distance');
        $ip = Arr::get($params, 'ip');

        if ($timesheet && ! $timesheet->out_at->value) {
            $inAt = Carbon::parse($timesheet->in_at->value);

            if ($inAt->diffInMinutes($storeDatetime) < 2) {
                throw ValidationException::withMessages(['message' => trans('employee.attendance.timesheet.minimum_diff_between_clock_in_out', ['attribute' => 2])]);
            }

            $timesheet->out_at = $storeDatetime?->toDateTimeString();
            $timesheet->setMeta([
                'out' => [
                    'self' => true,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'distance' => $distance,
                    'ip' => $ip,
                ],
            ]);
            $timesheet->save();
        } else {
            $timesheet = Timesheet::forceCreate([
                'employee_id' => $employee->id,
                'work_shift_id' => $employeeWorkShift?->work_shift_id,
                'date' => $date,
                'in_at' => $storeDatetime?->toDateTimeString(),
                'meta' => [
                    'in' => [
                        'self' => true,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'distance' => $distance,
                        'ip' => $ip,
                    ],
                ],
            ]);
        }

        return $timesheet;
    }

    private function findEmployeeWorkShift(Employee $employee, string $date): ?EmployeeWorkShift
    {
        return EmployeeWorkShift::query()
            ->select('work_shifts.records', 'employee_work_shifts.employee_id', 'employee_work_shifts.work_shift_id')
            ->join('work_shifts', function ($join) {
                $join->on('employee_work_shifts.work_shift_id', '=', 'work_shifts.id');
            })
            ->whereEmployeeId($employee->id)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    private function ensureAttendanceNotSynched(Employee $employee, string $date)
    {
        $attendanceExists = Attendance::query()
            ->where('date', $date)
            ->whereEmployeeId($employee->id)
            ->whereIsTimeBased(1)
            ->exists();

        if ($attendanceExists) {
            throw ValidationException::withMessages(['message' => trans('employee.attendance.could_not_perform_if_attendance_synched')]);
        }
    }

    private function validateInput(?Timesheet $timesheet, array $params = [])
    {
        $type = Arr::get($params, 'type');

        if (! $type) {
            return;
        }

        if (! $timesheet && $type == self::CLOCK_OUT) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        if ($timesheet && ! $timesheet->out_at && $type == self::CLOCK_IN) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }
    }

    private function checkDateForOvernightShift(?EmployeeWorkShift $employeeWorkShift = null, ?string $datetime = null): string
    {
        if (! $datetime) {
            $datetime = CalHelper::toDateTime(now()->toDateTimeString());
        }

        $date = Carbon::parse($datetime)->format('Y-m-d');
        $time = Carbon::parse($datetime)->format('H:i:s');
        $previousDay = strtolower(Carbon::parse($datetime)->subDay()->format('l'));

        if (! $employeeWorkShift) {
            return $date;
        }

        $previousDayRecord = collect(json_decode($employeeWorkShift->records, true))->firstWhere('day', $previousDay);

        if ($previousDayRecord && Arr::get($previousDayRecord, 'is_overnight') && $time <= self::OVERNIGHT_THRESHOLD) {
            $date = Carbon::parse($datetime)->subDay()->format('Y-m-d');
        }

        return $date;
    }
}
