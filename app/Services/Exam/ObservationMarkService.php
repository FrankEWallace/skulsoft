<?php

namespace App\Services\Exam;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Http\Resources\Exam\ExamResource;
use App\Http\Resources\Student\StudentResource;
use App\Models\Academic\Batch;
use App\Models\Exam\Exam;
use App\Models\Exam\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ObservationMarkService
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

        if (! $schedule->observation_id) {
            throw ValidationException::withMessages(['message' => trans('exam.could_not_record_observation_mark_without_observation_parameter')]);
        }

        $observation = $schedule->observation;

        $parameters = collect($observation->records)
            ->map(function ($record) {
                return [
                    'name' => Arr::get($record, 'name'),
                    'code' => Arr::get($record, 'code'),
                    'max_mark' => Arr::get($record, 'max_mark'),
                    'comment' => Arr::get($record, 'comment'),
                ];
            });

        $notApplicableStudents = Arr::get($schedule->details, 'not_applicable_students_for_observation', []);

        $comments = collect(Arr::get($schedule->details, 'observation_comments') ?? []);
        $recordMarks = Arr::get($schedule->details, 'observation_marks') ?? [];

        foreach ($students as $student) {
            $comment = Arr::get($comments->firstWhere('uuid', $student->uuid), 'comment');

            $marks = [];
            foreach ($parameters as $parameter) {
                $parameterCode = Arr::get($parameter, 'code');

                $parameterMarks = collect($recordMarks)->firstWhere('code', $parameterCode);

                $studentMarks = $parameterMarks['marks'] ?? [];

                $studentMark = collect($studentMarks)->firstWhere('uuid', $student->uuid);

                $marks[] = [
                    'code' => $parameterCode,
                    'name' => Arr::get($parameter, 'name'),
                    'max_mark' => Arr::get($parameter, 'max_mark'),
                    'obtained_mark' => $studentMark['obtained_mark'] ?? '',
                    'comment' => $studentMark['comment'] ?? '',
                ];
            }

            $student->marks = $marks;
            $student->comment = $comment;
            $student->is_not_applicable = in_array($student->uuid, $notApplicableStudents) ? true : false;
            $student->has_exam_mark = true;
        }

        return StudentResource::collection($students)
            ->additional([
                'meta' => [
                    'parameters' => $parameters,
                    'observation_mark_recorded' => Arr::get($schedule->details, 'observation_marks') ? true : false,
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
        $details['observation_marks'] = $marks;
        $details['observation_comments'] = $request->comments;
        $schedule->details = $details;
        $schedule->setConfig([
            'not_applicable_students_for_observation' => $notApplicableStudents,
        ]);
        $schedule->save();
    }

    public function remove(Request $request)
    {
        $data = $this->validateInput($request);

        $schedule = $data['schedule'];

        $details = $schedule->details;
        unset($details['observation_marks']);
        unset($details['observation_comments']);
        $schedule->details = $details;

        $schedule->setConfig([
            'not_applicable_students_for_observation' => [],
        ]);
        $schedule->save();
    }
}
