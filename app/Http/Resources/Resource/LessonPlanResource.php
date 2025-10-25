<?php

namespace App\Http\Resources\Resource;

use App\Enums\Resource\LessonPlanStatus;
use App\Http\Resources\Academic\BatchSubjectRecordResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class LessonPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'topic' => $this->topic,
            'topic_excerpt' => Str::summary($this->topic, 100),
            'records' => BatchSubjectRecordResource::collection($this->whenLoaded('records')),
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'details' => collect($this->details)->map(function ($detail) {
                return [
                    'uuid' => (string) Str::uuid(),
                    'heading' => $detail['heading'],
                    'description' => $detail['description'],
                ];
            }),
            'status' => LessonPlanStatus::getDetail($this->status),
            'is_locked' => $this->is_locked,
            'is_editable' => $this->is_editable,
            'is_deletable' => $this->is_deletable,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
