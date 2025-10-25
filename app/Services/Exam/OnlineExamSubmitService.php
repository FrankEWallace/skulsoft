<?php

namespace App\Services\Exam;

use App\Enums\Exam\OnlineExamQuestionType;
use App\Enums\Exam\OnlineExamType;
use App\Models\Exam\OnlineExam;
use App\Models\Exam\OnlineExamSubmission;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class OnlineExamSubmitService
{
    private function isLive(OnlineExam $onlineExam): void
    {
        if (! $onlineExam->is_live) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function getQuestions(OnlineExam $onlineExam): OnlineExam
    {
        $this->isLive($onlineExam);

        $student = $this->getStudent();

        $onlineExam->load(['questions', 'media', 'submissions' => function ($query) use ($student) {
            $query->where('student_id', $student->id);
        }]);

        return $onlineExam;
    }

    private function getStudent(): Student
    {
        $student = Student::query()
            ->auth()
            ->first();

        if (! $student) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        return $student;
    }

    public function startSubmission(Request $request, OnlineExam $onlineExam): mixed
    {
        $this->isLive($onlineExam);

        $student = $this->getStudent();

        $onlineExamRecord = OnlineExamSubmission::query()
            ->where('online_exam_id', $onlineExam->id)
            ->where('student_id', $student->id)
            ->first();

        if ($onlineExamRecord) {
            return $onlineExamRecord->started_at;
        }

        $startedAt = now()->toDateTimeString();

        $onlineExamRecord = OnlineExamSubmission::forceCreate([
            'online_exam_id' => $onlineExam->id,
            'student_id' => $student->id,
            'started_at' => $startedAt,
            'meta' => [
                'start_ip_address' => $request->ip(),
            ],
        ]);

        return \Cal::dateTime($startedAt);
    }

    public function store(Request $request, OnlineExam $onlineExam): mixed
    {
        $this->isLive($onlineExam);

        $student = $this->getStudent();

        $onlineExamRecord = OnlineExamSubmission::query()
            ->where('online_exam_id', $onlineExam->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $onlineExamRecord) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $questions = $onlineExam->questions;

        $inputQuestions = collect($request->questions ?? []);

        $inputQuestions = $inputQuestions->filter(function ($question) use ($questions) {
            return in_array($question['uuid'], $questions->pluck('uuid')->all());
        });

        $totalObtainedMark = 0;
        $submittedAnswerCount = 0;

        $answers = $inputQuestions
            ->map(function ($inputQuestion) use ($questions, $onlineExam) {
                $question = $questions->firstWhere('uuid', $inputQuestion['uuid']);

                $isCorrect = null;
                $obtainedMark = 0;
                if ($question->type == OnlineExamQuestionType::MCQ) {
                    $correctOption = Arr::get(collect($question->options)->firstWhere('is_correct', true), 'title');
                    $isCorrect = $correctOption == $inputQuestion['answer'];

                    if ($isCorrect) {
                        $obtainedMark = $question->mark;
                    } elseif (! $isCorrect && $onlineExam->getConfig('has_negative_marking', false)) {
                        $obtainedMark = -1 * round($question->mark * ($onlineExam->getConfig('negative_mark_percent_per_question', 0) / 100), 2);
                    }
                }

                return [
                    'uuid' => $inputQuestion['uuid'],
                    'answer' => $inputQuestion['answer'],
                    'is_correct' => $isCorrect,
                    'obtained_mark' => $obtainedMark,
                ];
            });

        $totalObtainedMark = $answers->sum('obtained_mark');

        if ($onlineExam->type == OnlineExamType::MCQ) {
            $onlineExamRecord->evaluated_at = now()->toDateTimeString();
        }
        $onlineExamRecord->obtained_mark = $totalObtainedMark;
        $onlineExamRecord->answers = $answers;
        $onlineExamRecord->save();

        $submittedAnswerCount = collect($answers)->filter(function ($answer) {
            return ! empty($answer['answer']);
        })->count();

        return $submittedAnswerCount;
    }

    public function finishSubmission(Request $request, OnlineExam $onlineExam): mixed
    {
        $this->isLive($onlineExam);

        $student = $this->getStudent();

        $onlineExamRecord = OnlineExamSubmission::query()
            ->where('online_exam_id', $onlineExam->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $onlineExamRecord) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $onlineExamRecord->submitted_at = now()->toDateTimeString();

        if ($onlineExam->type == OnlineExamType::MCQ) {
            $onlineExamRecord->evaluated_at = now()->toDateTimeString();
        }

        $onlineExamRecord->setMeta([
            'end_ip_address' => $request->ip(),
        ]);
        $onlineExamRecord->save();

        return $onlineExamRecord->submitted_at;
    }
}
