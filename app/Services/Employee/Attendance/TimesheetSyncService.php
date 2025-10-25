<?php

namespace App\Services\Employee\Attendance;

use App\Concerns\SubordinateAccess;
use App\Enums\Employee\Attendance\TimesheetStatus;
use App\Models\Calendar\Holiday;
use App\Models\Employee\Attendance\Attendance;
use App\Models\Employee\Attendance\Timesheet;
use App\Models\Employee\Attendance\Type as AttendanceType;
use App\Models\Employee\Employee;
use App\Models\Employee\WorkShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TimesheetSyncService
{
    use SubordinateAccess;

    const MAX_SYNC_COUNT = 1000;

    public function sync(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date',
        ]);

        $accessibleEmployeeIds = $this->getAccessibleEmployeeIds();

        $employees = Str::toArray($request->query('employees'));

        $employeeIds = Employee::query()
            ->whereIn('id', $accessibleEmployeeIds)
            ->when($employees, function ($q) use ($employees) {
                $q->whereIn('uuid', $employees);
            })
            ->pluck('id')
            ->all();

        $employeeWorkShifts = WorkShift::query()
            ->with(['workShift'])
            ->whereIn('employee_id', $employeeIds)
            ->get();

        $timesheets = Timesheet::query()
            ->with(['workShift'])
            ->whereIn('employee_id', $employeeIds)
            ->whereNull('status')
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ])->get();

        if ($timesheets->count() > self::MAX_SYNC_COUNT) {
            throw ValidationException::withMessages(['message' => trans('employee.attendance.timesheet.max_sync_count_limit_exceed')]);
        }

        $attendanceTypes = AttendanceType::byTeam()->get()->map(function ($type) {
            return [
                'type' => $type->category->value,
                'id' => $type->id,
            ];
        })->toArray();

        $holidays = Holiday::query()
            ->whereOverlapping($request->start_date, $request->end_date)
            ->get();

        $date = Carbon::parse($request->start_date);

        while ($date <= Carbon::parse($request->end_date)) {
            $dateWiseTimesheets = $timesheets->where('date.value', $date->toDateString());

            $isHoliday = $holidays->where('start_date.value', '<=', $date->toDateString())
                ->where('end_date.value', '>=', $date->toDateString())
                ->first() ? true : false;

            foreach ($employeeIds as $employeeId) {
                $employeeTimesheets = $dateWiseTimesheets->where('employee_id', $employeeId)->sortBy('in_at.value');

                $attendance = Attendance::query()
                    ->whereEmployeeId($employeeId)
                    ->where('date', $date->toDateString())
                    ->first();

                $employeeAllWorkShift = $employeeWorkShifts->where('employee_id', $employeeId);

                $employeeWorkShift = $employeeAllWorkShift
                    ->where('start_date.value', '<=', $date->toDateString())
                    ->where('end_date.value', '>=', $date->toDateString())
                    ->first();

                if ($employeeWorkShift) {
                    $employeeWorkShiftOfDay = collect($employeeWorkShift->workShift->records)->where('day', strtolower($date->englishDayOfWeek))->first();
                } else {
                    $employeeWorkShiftOfDay = [];
                }

                \DB::beginTransaction();

                $this->checkIfAttendanceExists(
                    attendance: $attendance,
                    date: $date,
                    employeeId: $employeeId
                );

                $this->markAttendance(
                    attendance: $attendance,
                    employeeTimesheets: $employeeTimesheets,
                    employeeWorkShiftOfDay: $employeeWorkShiftOfDay,
                    date: $date,
                    attendanceTypes: $attendanceTypes,
                    employeeId: $employeeId,
                    isHoliday: $isHoliday
                );

                \DB::commit();
            }

            $date->addDay(1);
        }
    }

    private function checkIfAttendanceExists(?Attendance $attendance, Carbon $date, int $employeeId)
    {
        if (! $attendance) {
            return;
        }

        return Timesheet::query()
            ->whereEmployeeId($employeeId)
            ->where('date', $date->toDateString())
            ->whereNull('status')
            ->update([
                'status' => $attendance->is_time_based
                    ? TimesheetStatus::ALREADY_SYNCHED
                    : TimesheetStatus::MANUAL_ATTENDANCE,
            ]);
    }

    private function markAttendance(?Attendance $attendance, Collection $employeeTimesheets, Carbon $date, array $attendanceTypes, array $employeeWorkShiftOfDay, int $employeeId, bool $isHoliday)
    {
        if ($attendance) {
            return;
        }

        if ($employeeTimesheets->count()) {
            return $this->markAttendanceIfTimesheetExists(
                employeeTimesheets: $employeeTimesheets,
                date: $date,
                attendanceTypes: $attendanceTypes,
                employeeId: $employeeId
            );
        }

        return $this->markAttendanceIfTimesheetDoesntExist(
            date: $date,
            employeeWorkShiftOfDay: $employeeWorkShiftOfDay,
            attendanceTypes: $attendanceTypes,
            employeeId: $employeeId,
            isHoliday: $isHoliday
        );
    }

    private function markAttendanceIfTimesheetExists(Collection $employeeTimesheets, Carbon $date, array $attendanceTypes, int $employeeId)
    {
        $employeeWorkShiftRecord = $this->findEmployeeWorkShiftRecord(
            employeeTimesheets: $employeeTimesheets,
            date: $date
        );

        if (! $employeeWorkShiftRecord) {
            $employeeWorkShiftRecord = [
                'is_holiday' => false,
                'is_overnight' => false,
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'full_day' => true,
            ];
        }

        $this->markAsHolidayIfWorkShiftRecordIsHoliday(
            date: $date,
            employeeWorkShiftRecord: $employeeWorkShiftRecord,
            attendanceTypes: $attendanceTypes,
            employeeId: $employeeId
        );

        $this->markAttendanceIfWorkShiftRecordIsNotHoliday(
            employeeTimesheets: $employeeTimesheets,
            date: $date,
            employeeWorkShiftRecord: $employeeWorkShiftRecord,
            attendanceTypes: $attendanceTypes,
            employeeId: $employeeId
        );
    }

    private function markAsHolidayIfWorkShiftRecordIsHoliday(Carbon $date, array $employeeWorkShiftRecord, array $attendanceTypes, int $employeeId)
    {
        if (! Arr::get($employeeWorkShiftRecord, 'is_holiday')) {
            return;
        }

        $this->markAsPresent(
            date: $date,
            attendanceTypes: $attendanceTypes,
            employeeId: $employeeId
        );
    }

    private function markAttendanceIfWorkShiftRecordIsNotHoliday(Collection $employeeTimesheets, Carbon $date, array $employeeWorkShiftRecord, array $attendanceTypes, int $employeeId)
    {
        if (Arr::get($employeeWorkShiftRecord, 'is_holiday')) {
            return;
        }

        $startDate = $date->toDateString();
        $endDate = $date->toDateString();
        if (Arr::get($employeeWorkShiftRecord, 'is_overnight')) {
            $endDate = Carbon::parse($endDate)->addDay(1)->toDateString();
        }

        $startTime = Carbon::parse($startDate.' '.Arr::get($employeeWorkShiftRecord, 'start_time'));
        $endTime = Carbon::parse($endDate.' '.Arr::get($employeeWorkShiftRecord, 'end_time'));

        $totalShiftDuration = abs($endTime->diffInMinutes($startTime));

        $firstInTime = Carbon::parse($employeeTimesheets->first()->in_at->value);
        $lastOutTime = Carbon::parse($employeeTimesheets->last()->out_at->value);

        $isLate = false;
        $isEarlyLeaving = false;

        if ($firstInTime->gt($startTime) && abs($firstInTime->diffInMinutes($startTime)) > config('config.employee.late_grace_period')) {
            $isLate = true;
        }

        if ($lastOutTime->lt($endTime) && abs($lastOutTime->diffInMinutes($endTime)) > config('config.employee.early_leaving_grace_period')) {
            $isEarlyLeaving = true;
        }

        $totalWorkDuration = 0;
        foreach ($employeeTimesheets as $employeeTimesheet) {
            $totalWorkDuration += abs(Carbon::parse($employeeTimesheet->out_at->value)->diffInMinutes($employeeTimesheet->in_at->value));
        }

        $options = [];
        if ($isLate) {
            $options['is_late'] = true;
            $options['late_duration'] = abs($firstInTime->diffInMinutes($startTime));
        }

        if ($isEarlyLeaving) {
            $options['is_early_leaving'] = true;
            $options['early_leaving_duration'] = abs($lastOutTime->diffInMinutes($endTime));
        }

        if ($totalWorkDuration >= $totalShiftDuration) {
            $options['is_overtime'] = true;
            $options['overtime_duration'] = $totalWorkDuration - $totalShiftDuration;
        }

        if (Arr::get($employeeWorkShiftRecord, 'full_day')) {
            $attendance = $this->markAsPresent(
                date: $date,
                attendanceTypes: $attendanceTypes,
                employeeId: $employeeId,
                options: []
            );
        } else {
            if ($totalWorkDuration >= $totalShiftDuration || ($totalShiftDuration - $totalWorkDuration <= config('config.employee.present_grace_period'))) {
                $attendance = $this->markAsPresent(
                    date: $date,
                    attendanceTypes: $attendanceTypes,
                    employeeId: $employeeId,
                    options: $options
                );
            } elseif ($totalWorkDuration < $totalShiftDuration && $totalWorkDuration > ($totalShiftDuration / 2)) {
                $attendance = $this->markAsHalfDay(
                    date: $date,
                    attendanceTypes: $attendanceTypes,
                    employeeId: $employeeId,
                    options: $options
                );
            } elseif ($totalWorkDuration < ($totalShiftDuration / 2)) {
                $attendance = $this->markAsAbsent(
                    date: $date,
                    attendanceTypes: $attendanceTypes,
                    employeeId: $employeeId
                );
            }
        }

        if ($attendance) {
            Timesheet::query()
                ->whereEmployeeId($employeeId)
                ->where('date', $date)
                ->update(['status' => TimesheetStatus::OK]);
        } else {
            Timesheet::query()
                ->whereEmployeeId($employeeId)
                ->where('date', $date)
                ->update(['status' => TimesheetStatus::OK]);
        }
    }

    private function markAttendanceIfTimesheetDoesntExist(Carbon $date, array $employeeWorkShiftOfDay, array $attendanceTypes, int $employeeId, bool $isHoliday)
    {
        if ($isHoliday) {
            return $this->markAsHoliday(
                date: $date,
                attendanceTypes: $attendanceTypes,
                employeeId: $employeeId,
            );
        }

        if ($employeeWorkShiftOfDay) {
            if (Arr::get($employeeWorkShiftOfDay, 'is_holiday')) {
                return $this->markAsHoliday(
                    date: $date,
                    attendanceTypes: $attendanceTypes,
                    employeeId: $employeeId,
                );
            }
        }

        return $this->markAsAbsent(
            date: $date,
            attendanceTypes: $attendanceTypes,
            employeeId: $employeeId,
        );
    }

    private function markAsPresent(Carbon $date, array $attendanceTypes, int $employeeId, array $options = []): ?Attendance
    {
        $attendanceTypeId = $this->getAttendanceTypeId($attendanceTypes, 'present');

        if (! $attendanceTypeId) {
            return null;
        }

        return Attendance::forceCreate([
            'employee_id' => $employeeId,
            'date' => $date->toDateString(),
            'attendance_type_id' => $attendanceTypeId,
            'is_time_based' => true,
            'meta' => $options,
        ]);
    }

    private function markAsHalfDay(Carbon $date, array $attendanceTypes, int $employeeId, array $options = []): ?Attendance
    {
        $attendanceTypeId = $this->getAttendanceTypeId($attendanceTypes, 'half_day');

        if (! $attendanceTypeId) {
            $attendanceTypeId = $this->getAttendanceTypeId($attendanceTypes, 'present');
        }

        if (! $attendanceTypeId) {
            return null;
        }

        return Attendance::forceCreate([
            'employee_id' => $employeeId,
            'date' => $date->toDateString(),
            'attendance_type_id' => $attendanceTypeId,
            'is_time_based' => true,
            'meta' => $options,
        ]);
    }

    private function markAsHoliday(Carbon $date, array $attendanceTypes, int $employeeId): ?Attendance
    {
        $attendanceTypeId = $this->getAttendanceTypeId($attendanceTypes, 'holiday');

        if (! $attendanceTypeId) {
            return null;
        }

        return Attendance::forceCreate([
            'employee_id' => $employeeId,
            'date' => $date->toDateString(),
            'attendance_type_id' => $attendanceTypeId,
            'is_time_based' => true,
        ]);
    }

    private function markAsAbsent(Carbon $date, array $attendanceTypes, int $employeeId): ?Attendance
    {
        if ($date->toDateString() > today()->toDateString()) {
            return null;
        }

        $attendanceTypeId = $this->getAttendanceTypeId($attendanceTypes, 'absent');

        if (! $attendanceTypeId) {
            return null;
        }

        return Attendance::forceCreate([
            'employee_id' => $employeeId,
            'date' => $date->toDateString(),
            'attendance_type_id' => $attendanceTypeId,
            'is_time_based' => true,
        ]);
    }

    private function getAttendanceTypeId(array $attendanceTypes, string $attendanceType)
    {
        $type = Arr::first($attendanceTypes, function ($item) use ($attendanceType) {
            return Arr::get($item, 'type') == $attendanceType;
        });

        return Arr::get($type, 'id');
    }

    private function findEmployeeWorkShiftRecord(Collection $employeeTimesheets, Carbon $date)
    {
        $employeeWorkShift = $employeeTimesheets->first()?->workShift;

        if (! $employeeWorkShift) {
            return;
        }

        return Arr::first($employeeWorkShift->records, function ($item) use ($date) {
            return Arr::get($item, 'day') == strtolower($date->englishDayOfWeek);
        });
    }
}
