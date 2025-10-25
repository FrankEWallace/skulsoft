<?php

namespace App\Services\Integration;

use App\Actions\Employee\Attendance\StoreTimesheet;
use App\Helpers\CalHelper;
use App\Models\Device;
use App\Models\Employee\Attendance\Timesheet;
use App\Models\Employee\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class DeviceTimesheetService
{
    public function store(Request $request)
    {
        if (! config('config.employee.allow_employee_clock_in_out_via_device')) {
            throw ValidationException::withMessages(['message' => 'Attendance cannot be marked via device.', 'code' => 100]);
        }

        $device = Device::where('token', $request->token)->first();

        if (! $device) {
            throw ValidationException::withMessages(['message' => 'Invalid token.', 'code' => 101]);
        }

        $employee = Employee::query()
            ->whereTeamId($device->team_id)
            ->whereCodeNumber($request->user_id)
            ->where('joining_date', '<=', today()->toDateString())
            ->where(function ($q) {
                $q->whereNull('leaving_date')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('leaving_date')
                            ->where('leaving_date', '>=', today()->toDateString());
                    });
            })
            ->first();

        if (! $employee) {
            throw ValidationException::withMessages(['message' => 'Invalid Employee code.', 'code' => 102]);
        }

        $datetime = $request->datetime;

        if ($datetime && ! CalHelper::validateDateFormat($datetime, 'Y-m-d H:i:s')) {
            $datetime = now()->timezone(config('config.system.timezone'))->toDateTimeString();
        }

        // no need to check below
        // $datetime = $request->datetime ? : now()->timezone(config('config.system.timezone'))->toDateTimeString();

        (new StoreTimesheet)->execute($employee, $datetime);

        return ['message' => 'Attendance marked successfully.', 'code' => 200];
    }

    public function import(Request $request)
    {
        if (! config('config.employee.allow_employee_clock_in_out_via_device')) {
            throw ValidationException::withMessages(['message' => 'Attendance cannot be marked via device.', 'code' => 100]);
        }

        $device = Device::where('token', $request->token)->first();

        if (! $device) {
            throw ValidationException::withMessages(['message' => 'Invalid token.', 'code' => 101]);
        }
        $importTimesheets = $request->attendance;

        $codeNumbers = array_unique(Arr::pluck($importTimesheets, 'user_id'));

        $employees = Employee::query()
            ->whereTeamId($device->team_id)
            ->whereIn('code_number', $codeNumbers)
            ->where('joining_date', '<=', today()->toDateString())
            ->where(function ($q) {
                $q->whereNull('leaving_date')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('leaving_date')
                            ->where('leaving_date', '>=', today()->toDateString());
                    });
            })
            ->get();

        $groupedTimesheets = [];
        foreach ($importTimesheets as $timesheet) {
            $employeeCode = Arr::get($timesheet, 'user_id');
            $datetime = CalHelper::storeDateTime(Arr::get($timesheet, 'datetime'));

            if (! isset($groupedTimesheets[$employeeCode])) {
                $groupedTimesheets[$employeeCode] = [];
            }

            $groupedTimesheets[$employeeCode][] = $datetime;
        }

        foreach ($groupedTimesheets as &$timesheets) {
            sort($timesheets);
        }

        foreach ($groupedTimesheets as $employeeCode => $timesheets) {
            $employee = $employees->firstWhere('code_number', $employeeCode);

            if (! $employee) {
                continue;
            }

            $inTime = null;
            $outTime = null;
            $lastProcessedTime = null;

            foreach ($timesheets as $datetime) {
                if ($lastProcessedTime && abs($datetime->diffInMinutes($lastProcessedTime)) < 5) {
                    continue;
                }

                if (is_null($inTime)) {
                    $inTime = $datetime;
                } elseif (is_null($outTime)) {
                    $outTime = $datetime;
                } else {
                    $existingTimesheet = Timesheet::where('employee_id', $employee->id)
                        ->whereDate('date', $inTime->toDateString())
                        ->where('in_at', $inTime)
                        ->where('out_at', $outTime)
                        ->first();

                    if (! $existingTimesheet) {
                        Timesheet::create([
                            'employee_id' => $employee->id,
                            'date' => $inTime->toDateString(),
                            'in_at' => $inTime,
                            'out_at' => $outTime,
                        ]);
                    }

                    $inTime = $datetime;
                    $outTime = null;
                }

                $lastProcessedTime = $datetime;
            }

            if ($inTime && ! $outTime) {
                $existingTimesheet = Timesheet::where('employee_id', $employee->id)
                    ->whereDate('date', $inTime->toDateString())
                    ->where('in_at', $inTime)
                    ->whereNull('out_at')
                    ->first();

                if (! $existingTimesheet) {
                    Timesheet::create([
                        'employee_id' => $employee->id,
                        'date' => $inTime->toDateString(),
                        'in_at' => $inTime,
                        'out_at' => null,
                    ]);
                }
            } elseif ($inTime && $outTime) {
                $existingTimesheet = Timesheet::where('employee_id', $employee->id)
                    ->whereDate('date', $inTime->toDateString())
                    ->where('in_at', $inTime)
                    ->where('out_at', $outTime)
                    ->first();

                if (! $existingTimesheet) {
                    Timesheet::create([
                        'employee_id' => $employee->id,
                        'date' => $inTime->toDateString(),
                        'in_at' => $inTime,
                        'out_at' => $outTime,
                    ]);
                }
            }
        }
    }

    public function importClockInClockOut(Request $request)
    {
        if (! config('config.employee.allow_employee_clock_in_out_via_device')) {
            throw ValidationException::withMessages(['message' => 'Attendance cannot be marked via device.', 'code' => 100]);
        }

        $device = Device::where('token', $request->token)->first();

        if (! $device) {
            throw ValidationException::withMessages(['message' => 'Invalid token.', 'code' => 101]);
        }

        $importTimesheets = $request->attendance;
        $codeNumbers = array_unique(Arr::pluck($importTimesheets, 'user_id'));

        $employees = Employee::query()
            ->whereTeamId($device->team_id)
            ->whereIn('code_number', $codeNumbers)
            ->where('joining_date', '<=', today()->toDateString())
            ->where(function ($q) {
                $q->whereNull('leaving_date')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('leaving_date')
                            ->where('leaving_date', '>=', today()->toDateString());
                    });
            })
            ->get();

        foreach ($importTimesheets as $importTimesheet) {
            $employee = $employees->firstWhere('code_number', Arr::get($importTimesheet, 'user_id'));

            if (! $employee) {
                continue;
            }

            $inAt = CalHelper::storeDateTime(Arr::get($importTimesheet, 'in_at'));
            $outAt = Arr::get($importTimesheet, 'out_at') ? CalHelper::storeDateTime(Arr::get($importTimesheet, 'out_at')) : null;

            $timesheet = Timesheet::query()
                ->firstOrCreate([
                    'employee_id' => $employee->id,
                    'date' => Arr::get($importTimesheet, 'date'),
                    'in_at' => $inAt,
                ]);

            if ($outAt) {
                $timesheet->out_at = $outAt;
                $timesheet->save();
            }
        }
    }
}
