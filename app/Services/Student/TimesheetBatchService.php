<?php

namespace App\Services\Student;

use App\Actions\Student\CalculateTimesheetDuration;
use App\Actions\Student\FetchBatchWiseStudent;
use App\Helpers\CalHelper;
use App\Http\Resources\Student\StudentResource;
use App\Models\Academic\Batch;
use App\Models\Academic\Period;
use App\Models\Student\Student;
use App\Models\Student\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TimesheetBatchService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    private function validateInput(Request $request): Batch
    {
        $request->validate([
            'batch' => 'required|uuid',
            'date' => 'required|date_format:Y-m-d',
        ]);

        return Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'batch');
    }

    public function fetch(Request $request)
    {
        $batch = $this->validateInput($request);

        $request->merge(['select_all' => true]);

        $students = (new FetchBatchWiseStudent)->execute($request->all());

        $timesheets = Timesheet::query()
            ->whereHas('student', function ($query) use ($batch) {
                $query->where('batch_id', $batch->id);
            })
            ->where('date', '=', $request->date)
            ->get();

        $students = $students->map(function ($student) use ($timesheets) {
            $timesheet = $timesheets->firstWhere('student_id', $student->id);
            $student->in_at = $timesheet?->in_at;
            $student->out_at = $timesheet?->out_at;
            $student->status = $timesheet?->status;

            return $student;
        });

        $request->merge(['has_timesheet' => true]);

        return StudentResource::collection($students)
            ->additional([
                'meta' => [],
            ]);
    }

    public function store(Request $request)
    {
        $batch = $this->validateInput($request);

        $request->merge(['select_all' => true]);

        $students = (new FetchBatchWiseStudent)->execute($request->all(), true);

        if (array_diff(Arr::pluck($request->students, 'uuid'), Arr::pluck($students, 'uuid'))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $period = Period::find(auth()->user()->current_period_id);

        foreach ($request->students as $index => $input) {
            $inAt = Arr::get($input, 'in_at') ?: null;
            $outAt = Arr::get($input, 'out_at') ?: null;

            if (! $inAt && $outAt) {
                throw ValidationException::withMessages(['students.'.$index.'.in_at' => trans('student.timesheet.could_not_perform_if_empty_in_at')]);
            }

            if ($inAt && $outAt && $inAt > $outAt) {
                throw ValidationException::withMessages(['students.'.$index.'.in_at' => trans('student.timesheet.start_time_should_less_than_end_time')]);
            }

            $student = Student::where('uuid', Arr::get($input, 'uuid'))->first();

            if (! $inAt && ! $outAt) {
                Timesheet::query()
                    ->where('student_id', $student->id)
                    ->where('date', '=', $request->date)
                    ->delete();

                continue;
            }

            $inAt = $inAt ? CalHelper::storeDateTime($request->date.' '.$inAt)?->toTimeString() : null;
            $outAt = $outAt ? CalHelper::storeDateTime($request->date.' '.$outAt)?->toTimeString() : null;

            $timesheet = Timesheet::query()
                ->where('student_id', $student->id)
                ->where('date', '=', $request->date)
                ->first();

            if (! $timesheet) {
                $timesheet = Timesheet::forceCreate([
                    'student_id' => $student->id,
                    'date' => $request->date,
                    'in_at' => $inAt,
                    'out_at' => $outAt,
                    'is_manual' => true,
                    'remarks' => null,
                ]);
            } else {
                $timesheet->update([
                    'is_manual' => $timesheet->in_at->value == $inAt && $timesheet->out_at->value == $outAt ? false : true,
                    'in_at' => $inAt,
                    'out_at' => $outAt,
                ]);
            }

            (new CalculateTimesheetDuration)->execute($timesheet, $period);
        }
    }
}
