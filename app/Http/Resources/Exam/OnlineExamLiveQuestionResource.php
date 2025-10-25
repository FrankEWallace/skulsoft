<?php

namespace App\Http\Resources\Exam;

use App\Enums\Exam\OnlineExamQuestionType;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class OnlineExamLiveQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $submission = $this->submissions->first();

        $answers = $submission?->answers ?? [];

        return [
            'uuid' => $this->uuid,
            'instructions' => $this->instructions,
            'media_token' => $this->getMeta('media_token'),
            'submitted_answer_count' => collect($answers)->filter(function ($answer) {
                return ! empty($answer['answer']);
            })->count(),
            'questions' => $this->questions->map(function ($question) use ($answers) {
                $answer = collect($answers)->firstWhere('uuid', $question->uuid);

                return [
                    'uuid' => $question->uuid,
                    'header' => $question->header,
                    'title' => $question->title,
                    'name' => Str::random(10),
                    'type' => OnlineExamQuestionType::getDetail($question->type),
                    'mark' => $question->mark,
                    'answer' => $answer['answer'] ?? null,
                    'options' => collect($question->options)->map(function ($option) {
                        return [
                            'uuid' => $option['uuid'],
                            'name' => Str::random(10),
                            'title' => $option['title'],
                            'label' => $option['title'],
                            'value' => $option['title'],
                        ];
                    }),
                ];
            }),
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
