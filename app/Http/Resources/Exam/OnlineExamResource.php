<?php

namespace App\Http\Resources\Exam;

use App\Enums\Exam\OnlineExamQuestionType;
use App\Enums\Exam\OnlineExamType;
use App\Http\Resources\Academic\BatchSubjectRecordResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class OnlineExamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $submissionRecord = false;
        $startedAt = null;
        $submittedAt = null;
        if ($request->boolean('submission') && auth()->user()->hasRole('student')) {
            $submissionRecord = true;
            $startedAt = \Cal::dateTime($this->submissions->first()?->started_at);
            $submittedAt = \Cal::dateTime($this->submissions->first()?->submitted_at);
        }

        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_date' => $this->end_date,
            'end_time' => $this->end_time,
            'period' => $this->start_time->formatted.' - '.$this->end_time->formatted,
            'duration' => $this->duration,
            'type' => OnlineExamType::getDetail($this->type),
            'records' => BatchSubjectRecordResource::collection($this->whenLoaded('records')),
            'pass_percentage' => $this->pass_percentage,
            'has_negative_marking' => $this->getConfig('has_negative_marking', false),
            'negative_mark_percent_per_question' => $this->getConfig('negative_mark_percent_per_question', 0),
            'result_published_at' => $this->result_published_at,
            'published_at' => $this->published_at,
            'upcoming_threshold' => 180,
            'is_upcoming' => $this->upcoming_time > 0 ? true : false,
            'is_live' => $this->is_live,
            'is_completed' => $this->is_completed,
            $this->mergeWhen($this->upcoming_time > 0, [
                'time_left' => $this->upcoming_time,
            ]),
            $this->mergeWhen($submissionRecord, [
                'started_at' => $startedAt,
                'submitted_at' => $submittedAt,
                $this->mergeWhen($request->boolean('show_details') && $this->is_completed && $this->result_published_at->value, [
                    'questions' => $this->getQuestions(),
                    'obtained_mark' => $this->getObtainedMark(),
                ]),
            ]),
            $this->mergeWhen(! auth()->user()->hasAnyRole(['student', 'guardian']), [
                'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
                'is_editable' => $this->is_editable,
                'is_deletable' => $this->is_deletable,
                'can_update_status' => $this->can_update_status,
                'can_manage_question' => $this->can_manage_question,
                'can_evaluate' => $this->can_evaluate,
                $this->mergeWhen($request->boolean('show_details'), [
                    'questions_count' => $this->questions_count,
                    'submissions_count' => $this->submissions_count,
                ]),
                'instructions' => $this->instructions,
                'description' => $this->description,
                'media_token' => $this->getMeta('media_token'),
                'media' => MediaResource::collection($this->whenLoaded('media')),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getQuestions(): array
    {
        if (! auth()->user()->hasRole('student')) {
            return [];
        }

        if (! $this->is_completed) {
            return [];
        }

        if (! $this->result_published_at->value) {
            return [];
        }

        $submission = $this->submissions->first();
        if (! $submission) {
            return [];
        }

        return $this->questions->map(function ($question) use ($submission) {
            $answer = collect($submission->answers)->firstWhere('uuid', $question->uuid);

            return [
                'uuid' => $question->uuid,
                'header' => $question->header,
                'type' => OnlineExamQuestionType::getDetail($question->type),
                'title' => $question->title,
                'options' => collect($question->options)->map(function ($option) {
                    return [
                        'uuid' => Arr::get($option, 'uuid'),
                        'title' => Arr::get($option, 'title'),
                        'is_correct' => (bool) Arr::get($option, 'is_correct'),
                    ];
                }),
                'max_mark' => $question->mark,
                'obtained_mark' => Arr::get($answer, 'obtained_mark'),
                'answer' => Arr::get($answer, 'answer'),
                'correct_answer' => Arr::get(collect($question->options)->firstWhere('is_correct'), 'title'),
            ];
        })->toArray();
    }

    private function getObtainedMark(): string
    {
        return ($this->submissions->first()?->obtained_mark ?? 0).'/'.$this->max_mark;
    }
}
