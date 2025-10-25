<?php

namespace App\Actions\Employee\Attendance;

use App\Helpers\CalHelper;
use App\Models\Employee\WorkShift as EmployeeWorkShift;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class CheckDateForOvernightShift
{
    const OVERNIGHT_THRESHOLD = '06:00:00';

    public function execute(?EmployeeWorkShift $employeeWorkShift = null, ?string $datetime = null): string
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
