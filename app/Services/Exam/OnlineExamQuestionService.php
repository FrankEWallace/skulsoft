<?php

namespace App\Services\Exam;

use App\Enums\Exam\OnlineExamQuestionType;
use App\Models\Exam\OnlineExam;
use App\Models\Exam\OnlineExamQuestion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OnlineExamQuestionService
{
    public function preRequisite(Request $request, OnlineExam $onlineExam): array
    {
        $types = OnlineExamQuestionType::getOptions();

        return compact('types');
    }

    public function findByUuidOrFail(OnlineExam $onlineExam, string $uuid): OnlineExamQuestion
    {
        return OnlineExamQuestion::query()
            ->whereOnlineExamId($onlineExam->id)
            ->whereUuid($uuid)
            ->getOrFail(trans('exam.online_exam.question.question'));
    }

    private function ensureCanManageQuestion(OnlineExam $onlineExam): void
    {
        if (! $onlineExam->can_manage_question) {
            throw ValidationException::withMessages([
                'message' => trans('user.errors.permission_denied'),
            ]);
        }

        if ($onlineExam->published_at->value) {
            throw ValidationException::withMessages([
                'message' => trans('exam.online_exam.already_published'),
            ]);
        }
    }

    public function create(Request $request, OnlineExam $onlineExam): OnlineExamQuestion
    {
        $this->ensureCanManageQuestion($onlineExam);

        \DB::beginTransaction();

        $question = OnlineExamQuestion::forceCreate($this->formatParams($request, $onlineExam));

        $onlineExam->max_mark = round($onlineExam->questions->sum('mark'), 2);
        $onlineExam->save();

        \DB::commit();

        return $question;
    }

    private function formatParams(Request $request, OnlineExam $onlineExam, ?OnlineExamQuestion $question = null): array
    {
        $formatted = [
            'type' => $request->type,
            'mark' => $request->mark,
            'title' => clean($request->title),
            'header' => clean($request->header),
            'options' => $request->options,
        ];

        if (! $question) {
            $formatted['position'] = OnlineExamQuestion::query()
                ->whereOnlineExamId($onlineExam->id)
                ->max('position') + 1;
            $formatted['online_exam_id'] = $onlineExam->id;
        }

        return $formatted;
    }

    public function update(Request $request, OnlineExam $onlineExam, OnlineExamQuestion $question): void
    {
        $this->ensureCanManageQuestion($onlineExam);

        \DB::beginTransaction();

        $question->forceFill($this->formatParams($request, $onlineExam, $question))->save();

        $onlineExam->max_mark = round($onlineExam->questions->sum('mark'), 2);
        $onlineExam->save();

        \DB::commit();
    }

    public function deletable(OnlineExam $onlineExam, OnlineExamQuestion $question): void
    {
        //
    }
}
