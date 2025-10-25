<?php

namespace App\Services\Student;

use App\Actions\Student\CalculateTimesheetDuration;
use App\Helpers\CalHelper;
use App\Http\Resources\Student\TimesheetResource;
use App\Models\Student\Student;
use App\Models\Student\Timesheet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimesheetActionService
{
    const CLOCK_IN = 'in';

    const CLOCK_OUT = 'out';

    private function isStudentClockInOutAllowed()
    {
        return config('config.student.allow_student_clock_in_out');
    }

    public function check(Request $request): array
    {
        $datetime = CalHelper::toDateTime(now()->toDateTimeString());
        $date = Carbon::parse($datetime)->format('Y-m-d');

        if (! $this->isStudentClockInOutAllowed()) {
            return [
                'has_timesheet' => false,
            ];
        }

        $student = Student::auth()->first();

        if (! $student) {
            return [
                'has_timesheet' => false,
            ];
        }

        $type = self::CLOCK_IN;

        $timesheets = Timesheet::query()
            ->whereStudentId($student->id)
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

        if (! $this->isStudentClockInOutAllowed()) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $student = Student::auth()->first();

        $params = [
            'type' => $request->type,
            'ip' => $request->ip(),
        ];

        $timesheet = Timesheet::query()
            ->whereStudentId($student->id)
            ->where('date', $date)
            ->first();

        if ($request->type == self::CLOCK_IN && $timesheet) {
            throw ValidationException::withMessages(['message' => trans('student.timesheet.already_clocked_in')]);
        }

        if ($request->type == self::CLOCK_OUT && ! $timesheet) {
            throw ValidationException::withMessages(['message' => trans('student.timesheet.not_clocked_in')]);
        }

        $fullDateTime = CalHelper::storeDateTime($datetime)->toDateTimeString();
        $datetime = CalHelper::storeDateTime($datetime)->toTimeString();

        if ($request->type == self::CLOCK_IN && ! $timesheet) {
            $timesheet = Timesheet::forceCreate([
                'student_id' => $student->id,
                'date' => $date,
                'in_at' => $fullDateTime,
            ]);
        } elseif ($request->type == self::CLOCK_OUT && $timesheet) {

            $durationBetweenClockRequest = config('config.student.duration_between_clock_request', 5);

            $inAt = Carbon::parse($timesheet->in_at->value);
            $outAt = Carbon::parse($fullDateTime);

            if ($inAt->diffInMinutes($outAt) < $durationBetweenClockRequest) {
                throw ValidationException::withMessages(['message' => trans('student.timesheet.recently_marked')]);
            }

            $timesheet->update([
                'out_at' => $datetime,
            ]);

            (new CalculateTimesheetDuration)->execute($timesheet);
        }

        $timesheet->refresh();

        $timesheets = Timesheet::query()
            ->whereStudentId($student->id)
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
