<?php

namespace App\Actions\Student;

use App\Models\Academic\Period;
use App\Models\Student\Timesheet;
use Carbon\Carbon;

class CalculateTimesheetDuration
{
    public function execute(Timesheet $timesheet, ?Period $period = null)
    {
        if (! $timesheet->in_at || ! $timesheet->out_at) {
            return;
        }

        if (! $period) {
            $period = Period::find(auth()->user()->current_period_id);
        }

        if (empty($period->getConfig('session_start_time')) || empty($period->getConfig('session_end_time'))) {
            return;
        }

        $sessionStartTime = Carbon::parse($period->getConfig('session_start_time'));
        $sessionEndTime = Carbon::parse($period->getConfig('session_end_time'));

        $inAt = Carbon::parse($timesheet->in_at->value);
        $outAt = Carbon::parse($timesheet->out_at->value);

        $lateComingDuration = $inAt->isAfter($sessionStartTime) ? $inAt->diffInMinutes($sessionStartTime, true) : 0;
        $earlyLeavingDuration = $outAt->isBefore($sessionEndTime) ? $sessionEndTime->diffInMinutes($outAt, true) : 0;

        $duration = $inAt->diffInMinutes($outAt);

        $extraDuration = 0;
        if ($inAt->isBefore($sessionStartTime)) {
            $extraDuration += $sessionStartTime->diffInMinutes($inAt, true);
        }
        if ($outAt->isAfter($sessionEndTime)) {
            $extraDuration += $outAt->diffInMinutes($sessionEndTime, true);
        }

        $timesheet->setMeta([
            'duration' => $duration,
            'extra_duration' => $extraDuration,
            'late_coming_duration' => $lateComingDuration,
            'early_leaving_duration' => $earlyLeavingDuration,
        ]);
        $timesheet->save();
    }
}
