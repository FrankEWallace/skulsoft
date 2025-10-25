<?php

namespace App\Services\Exam;

use App\Models\Exam\OnlineExam;
use App\Models\Exam\OnlineExamQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OnlineExamQuestionActionService
{
    public function reorder(Request $request, OnlineExam $onlineExam): void
    {
        $questions = $request->questions ?? [];

        $allQuestions = OnlineExamQuestion::query()
            ->whereOnlineExamId($onlineExam->id)
            ->get();

        foreach ($questions as $index => $questionItem) {
            $question = $allQuestions->firstWhere('uuid', Arr::get($questionItem, 'uuid'));

            if (! $question) {
                continue;
            }

            $question->position = $index + 1;
            $question->save();
        }
    }
}
