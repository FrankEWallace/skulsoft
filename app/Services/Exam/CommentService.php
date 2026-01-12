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

class CommentService
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

        $notApplicableStudents = Arr::get($schedule->details, 'not_applicable_students_for_comment', []);

        $comments = collect(Arr::get($schedule->details, 'comments') ?? []);

        foreach ($students as $student) {
            $studentComment = $comments->firstWhere('uuid', $student->uuid);
            $comment = Arr::get($studentComment, 'comment');
            $inchargeComment = Arr::get($studentComment, 'incharge_comment');
            $result = Arr::get($studentComment, 'result');

            $student->comment = $comment;
            $student->incharge_comment = $inchargeComment;
            $student->result = $result;
            $student->is_not_applicable = in_array($student->uuid, $notApplicableStudents) ? true : false;
            $student->has_exam_mark = true;
        }

        return StudentResource::collection($students)
            ->additional([
                'meta' => [
                    'comment_recorded' => count(Arr::get($schedule->details, 'comments', [])) ? true : false,
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
        $details['comments'] = $request->comments;
        $schedule->details = $details;
        $schedule->setConfig([
            'not_applicable_students_for_comment' => $notApplicableStudents,
        ]);
        $schedule->save();
    }

    public function remove(Request $request)
    {
        $data = $this->validateInput($request);

        $schedule = $data['schedule'];

        $details = $schedule->details;
        unset($details['comments']);
        $schedule->details = $details;

        $schedule->setConfig([
            'not_applicable_students_for_comment' => [],
        ]);
        $schedule->save();
    }
}
