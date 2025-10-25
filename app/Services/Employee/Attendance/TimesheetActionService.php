<?php

namespace App\Services\Employee\Attendance;

use App\Actions\Employee\Attendance\CheckDateForOvernightShift;
use App\Actions\Employee\Attendance\StoreTimesheet;
use App\Concerns\SubordinateAccess;
use App\Helpers\CalHelper;
use App\Http\Resources\Employee\Attendance\TimesheetResource;
use App\Models\Employee\Attendance\Timesheet;
use App\Models\Employee\Employee;
use App\Models\Employee\WorkShift as EmployeeWorkShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimesheetActionService
{
    use SubordinateAccess;

    const CLOCK_IN = 'in';

    const CLOCK_OUT = 'out';

    private function isEmployeeClockInOutAllowed()
    {
        return config('config.employee.allow_employee_clock_in_out');
    }

    private function isEmployeeGeolocationEnabled()
    {
        return config('config.employee.enable_geolocation_timesheet');
    }

    private function validateGeolocation(Request $request): float
    {
        if (! $this->isEmployeeGeolocationEnabled()) {
            return 0;
        }

        $geolocationLatitude = config('config.employee.geolocation_latitude');
        $geolocationLongitude = config('config.employee.geolocation_longitude');
        $geolocationRadius = config('config.employee.geolocation_radius');

        $request->validate([
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
        ]);

        $earthRadius = 6371;

        $lat1Rad = deg2rad($request->input('location.latitude'));
        $lat2Rad = deg2rad($geolocationLatitude);
        $lon1Rad = deg2rad($request->input('location.longitude'));
        $lon2Rad = deg2rad($geolocationLongitude);

        $latDiff = $lat2Rad - $lat1Rad;
        $lonDiff = $lon2Rad - $lon1Rad;

        $a = sin($latDiff / 2) ** 2 + cos($lat1Rad) * cos($lat2Rad) * sin($lonDiff / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = round($earthRadius * $c * 1000, 2);

        if ($distance > $geolocationRadius) {
            throw ValidationException::withMessages(['message' => trans('employee.attendance.timesheet.could_not_mark_attendance_outside_geolocation', ['distance' => $distance])]);
        }

        return $distance;
    }

    public function check(Request $request): array
    {
        $datetime = CalHelper::toDateTime(now()->toDateTimeString());
        $date = Carbon::parse($datetime)->format('Y-m-d');

        if (! $this->isEmployeeClockInOutAllowed()) {
            return [
                'has_timesheet' => false,
            ];
        }

        $employee = Employee::auth()->first();

        if (! $employee) {
            return [
                'has_timesheet' => false,
            ];
        }

        $employeeWorkShift = EmployeeWorkShift::query()
            ->select('work_shifts.records', 'employee_work_shifts.employee_id', 'employee_work_shifts.work_shift_id')
            ->join('work_shifts', function ($join) {
                $join->on('employee_work_shifts.work_shift_id', '=', 'work_shifts.id');
            })
            ->whereEmployeeId($employee->id)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        $date = (new CheckDateForOvernightShift)->execute($employeeWorkShift, $datetime);

        $type = self::CLOCK_IN;

        $timesheets = Timesheet::query()
            ->whereEmployeeId($employee->id)
            ->where('date', $date)
            ->orderBy('in_at', 'desc')
            ->get();

        if (is_null($timesheets->first())) {
            $type = self::CLOCK_IN;
        } elseif (empty($timesheets->first()?->out_at?->value)) {
            $type = self::CLOCK_OUT;
        }

        return [
            'has_timesheet' => true,
            'timesheet' => [
                'clock_in' => $type == self::CLOCK_IN ? true : false,
                'clock_out' => $type == self::CLOCK_OUT ? true : false,
            ],
            'timesheets' => TimesheetResource::collection($timesheets),
        ];
    }

    public function clock(Request $request)
    {
        $datetime = CalHelper::toDateTime(now()->toDateTimeString());
        $date = Carbon::parse($datetime)->format('Y-m-d');

        if (! $this->isEmployeeClockInOutAllowed()) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $distance = $this->validateGeolocation($request);

        $employee = Employee::auth()->first();

        $params = [
            'type' => $request->type,
            'latitude' => $request->input('location.latitude'),
            'longitude' => $request->input('location.longitude'),
            'distance' => $distance ?? null,
            'ip' => $request->ip(),
        ];

        $durationBetweenClockRequest = config('config.employee.duration_between_clock_request', 5);

        $lastTimesheet = Timesheet::query()
            ->whereEmployeeId($employee->id)
            ->where('date', today()->toDateString())
            ->where(function ($q) use ($durationBetweenClockRequest) {
                $q->where('in_at', '>=', now()->subMinutes($durationBetweenClockRequest)->toDateTimeString())
                    ->orWhere('out_at', '>=', now()->subMinutes($durationBetweenClockRequest)->toDateTimeString());
            })
            ->exists();

        if ($lastTimesheet) {
            throw ValidationException::withMessages(['message' => trans('employee.attendance.timesheet.recently_marked')]);
        }

        $timesheet = (new StoreTimesheet)->execute($employee, $datetime, $params);

        $timesheets = Timesheet::query()
            ->whereEmployeeId($employee->id)
            ->where('date', $date)
            ->orderBy('in_at', 'desc')
            ->get();

        return [
            'has_timesheet' => true,
            'timesheet' => TimesheetResource::make($timesheet),
            'timesheets' => TimesheetResource::collection($timesheets),
        ];
    }
}
