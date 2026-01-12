<?php

namespace App\Services\Exam;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Http\Resources\Exam\ExamResource;
use App\Http\Resources\Student\StudentResource;
use App\Domain\Academic\Models\Batch;
use App\Models\Exam\Exam;
use App\Models\Exam\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function preRequisite(Request $request)
    {
        $exams = ExamResource::collection(Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->get());

        return compact('exams');
    }

    private function validateInput(Request $request): array
    {
        $request->validate([
            'exam' => 'required|uuid',
            'batch' => 'required|uuid',
        ]);

        $exam = Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->whereUuid($request->exam)
            ->getOrFail(trans('exam.exam'), 'exam');

        $batch = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'batch');

        $schedule = Schedule::query()
            ->whereExamId($exam->id)
            ->whereBatchId($batch->id)
            ->getOrFail(trans('exam.schedule.schedule'));

        return [
            'exam' => $exam,
            'batch' => $batch,
            'schedule' => $schedule,
        ];
    }

    public function fetch(Request $request)
    {
        $data = $this->validateInput($request);

        $exam = $data['exam'];
        $batch = $data['batch'];
        $schedule = $data['schedule'];

        $request->merge([
            'select_all' => true,
        ]);

        $params = $request->all();
        if ($request->boolean('show_all_student')) {
            $params['status'] = 'all';
        }

        $students = (new FetchBatchWiseStudent)->execute($params);

        $notApplicableStudents = Arr::get($schedule->details, 'not_applicable_students_for_attendance', []);

        $attendances = collect(Arr::get($schedule->details, 'attendances') ?? []);

        foreach ($students as $student) {
            $studentAttendance = $attendances->firstWhere('uuid', $student->uuid);
            $comment = Arr::get($studentAttendance, 'comment');
            $attendance = Arr::get($studentAttendance, 'attendance');

            $student->comment = $comment;
            $student->attendance = $attendance;
            $student->is_not_applicable = in_array($student->uuid, $notApplicableStudents) ? true : false;
            $student->has_exam_mark = true;
        }

        return StudentResource::collection($students)
            ->additional([
                'meta' => [
                    'attendance_recorded' => count(Arr::get($schedule->details, 'attendances', [])) ? true : false,
                    'total_working_days' => Arr::get($schedule->details, 'total_working_days'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateInput($request);

        $exam = $data['exam'];
        $batch = $data['batch'];
        $schedule = $data['schedule'];

        $request->merge(['select_all' => true]);

        $params = $request->all();

        if ($request->boolean('show_all_student')) {
            $params['status'] = 'all';
        }

        $students = (new FetchBatchWiseStudent)->execute($params, true);

        if (array_diff(Arr::pluck($request->students, 'uuid'), Arr::pluck($students, 'uuid'))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $marks = $request->marks;
        $notApplicableStudents = $request->not_applicable_students ?? [];
        // foreach ($request->students as $input) {
        //     $student = collect($students)->where('uuid', Arr::get($input, 'uuid'))->first();
        // }

        $details = $schedule->details;
        $details['total_working_days'] = $request->total_working_days;
        $details['attendances'] = $request->attendances;
        $schedule->details = $details;
        $schedule->setConfig([
            'not_applicable_students_for_attendance' => $notApplicableStudents,
        ]);
        $schedule->save();
    }

    public function remove(Request $request)
    {
        $data = $this->validateInput($request);

        $schedule = $data['schedule'];

        $details = $schedule->details;
        unset($details['total_working_days']);
        unset($details['attendances']);
        $schedule->details = $details;

        $schedule->setConfig([
            'not_applicable_students_for_attendance' => [],
        ]);
        $schedule->save();
    }
}
