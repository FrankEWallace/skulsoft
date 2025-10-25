<?php

namespace App\Http\Resources\Exam;

use App\Enums\Exam\OnlineExamQuestionType;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OnlineExamSubmissionQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $submission = $this->submission;

        $answers = $submission?->answers ?? [];

        return [
            'uuid' => $submission->uuid,
            'student_name' => $submission?->student_name,
            'admission_number' => $submission?->admission_number,
            'batch_name' => $submission?->batch_name,
            'course_name' => $submission?->course_name,
            'started_at' => $submission?->started_at,
            'submitted_at' => $submission?->submitted_at,
            'submitted_answer_count' => collect($answers)->filter(function ($answer) {
                return ! empty($answer['answer']);
            })->count(),
            'evaluation_at' => $submission?->evaluation_at,
            'max_mark' => $this->max_mark,
            'obtained_mark' => $submission?->obtained_mark,
            'questions' => $this->questions->map(function ($question) use ($answers) {
                $answer = collect($answers)->firstWhere('uuid', $question->uuid);

                return [
                    'uuid' => $question->uuid,
                    'header' => $question->header,
                    'title' => $question->title,
                    'name' => Str::random(10),
                    'type' => OnlineExamQuestionType::getDetail($question->type),
                    'mark' => $question->mark,
                    'obtained_mark' => Arr::get($answer, 'obtained_mark'),
                    'comment' => Arr::get($answer, 'comment'),
                    'answer' => Arr::get($answer, 'answer'),
                    'correct_answer' => Arr::get(collect($question->options)->first(function ($option) {
                        return (bool) Arr::get($option, 'is_correct');
                    }), 'title'),
                    'options' => collect($question->options)->map(function ($option) {
                        return [
                            'uuid' => Arr::get($option, 'uuid'),
                            'name' => Str::random(10),
                            'title' => Arr::get($option, 'title'),
                            'label' => Arr::get($option, 'title'),
                            'value' => Arr::get($option, 'title'),
                            'is_correct' => (bool) Arr::get($option, 'is_correct'),
                        ];
                    }),
                ];
            }),
        ];
    }
}
