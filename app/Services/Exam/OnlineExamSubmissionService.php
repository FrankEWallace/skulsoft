<?php

namespace App\Services\Exam;

use App\Models\Exam\OnlineExam;
use App\Models\Exam\OnlineExamSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class OnlineExamSubmissionService
{
    public function preRequisite(Request $request, OnlineExam $onlineExam): array
    {
        return [];
    }

    public function findByUuidOrFail(OnlineExam $onlineExam, string $uuid): OnlineExamSubmission
    {
        return OnlineExamSubmission::query()
            ->whereOnlineExamId($onlineExam->id)
            ->whereUuid($uuid)
            ->getOrFail(trans('exam.online_exam.submission.submission'));
    }

    public function getSubmission(Request $request, OnlineExam $onlineExam, string $submission)
    {
        if (auth()->user()->hasAnyRole('student', 'guardian')) {
            return [];
        }

        $submission = OnlineExamSubmission::query()
            ->select('online_exam_submissions.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as student_name'), 'batches.name as batch_name', 'courses.name as course_name', 'admissions.code_number as admission_number')
            ->join('students', 'students.id', '=', 'online_exam_submissions.student_id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->join('contacts', 'contacts.id', '=', 'students.contact_id')
            ->join('batches', 'batches.id', '=', 'students.batch_id')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->where('online_exam_id', $onlineExam->id)
            ->where('online_exam_submissions.uuid', $submission)
            ->firstOrFail();

        return $submission;
    }

    public function evaluate(Request $request, OnlineExam $onlineExam, string $submission)
    {
        if ($onlineExam->result_published_at->value) {
            throw ValidationException::withMessages(['message' => trans('exam.online_exam.already_result_published')]);
        }

        $request->validate([
            'questions' => ['required', 'array'],
            'questions.*.uuid' => ['required', 'string', 'distinct'],
            'questions.*.mark' => ['required', 'numeric'],
        ]);

        $submission = OnlineExamSubmission::query()
            ->whereOnlineExamId($onlineExam->id)
            ->whereUuid($submission)
            ->firstOrFail();

        $answers = $submission->answers;
        $questions = $onlineExam->questions;

        $evaluatedAnswers = [];
        foreach ($request->questions as $index => $inputQuestion) {
            $question = $questions->firstWhere('uuid', Arr::get($inputQuestion, 'uuid'));

            if (! $question) {
                continue;
            }

            $obtainedMark = round(Arr::get($inputQuestion, 'obtained_mark'), 2);

            if ($obtainedMark < 0 && ! $onlineExam->getConfig('has_negative_marking', false)) {
                throw ValidationException::withMessages(['message' => trans('exam.online_exam.negative_mark_not_allowed')]);
            }

            if ($onlineExam->getConfig('has_negative_marking', false) && $obtainedMark < 0 && $obtainedMark < $question->mark * -1) {
                throw ValidationException::withMessages(['questions.'.$index.'.obtained_mark' => trans('validation.min.numeric', ['attribute' => trans('exam.online_exam.obtained_mark'), 'min' => $question->mark * -1])]);
            }

            if ($obtainedMark > $question->mark) {
                throw ValidationException::withMessages(['questions.'.$index.'.obtained_mark' => trans('validation.max.numeric', ['attribute' => trans('exam.online_exam.obtained_mark'), 'max' => $question->mark])]);
            }

            $answer = collect($answers)->firstWhere('uuid', $question->uuid);
            $answer['obtained_mark'] = (float) $obtainedMark;
            $answer['comment'] = Arr::get($inputQuestion, 'comment');

            $evaluatedAnswers[] = $answer;
        }

        $answers = collect($answers)->map(function ($answer) use ($evaluatedAnswers) {
            $evaluatedAnswer = collect($evaluatedAnswers)->firstWhere('uuid', $answer['uuid']);

            return $evaluatedAnswer ?? $answer;
        });

        $submission->obtained_mark = round(collect($answers)->sum('obtained_mark'), 2);
        $submission->answers = $answers;
        $submission->evaluated_at = now()->toDateTimeString();

        $submission->save();
    }

    public function deletable(OnlineExam $onlineExam, OnlineExamSubmission $submission): bool
    {
        if ($onlineExam->result_published_at->value) {
            throw ValidationException::withMessages(['message' => trans('exam.online_exam.already_result_published')]);
        }

        return true;
    }
}
