<?php

namespace App\Http\Resources\Exam;

use App\Http\Resources\Academic\SubjectRecordResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordResource extends JsonResource
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
            'subject_record' => SubjectRecordResource::make($this->whenLoaded('subjectRecord')),
            'assessment' => AssessmentResource::make($this->whenLoaded('assessment')),
            'date' => $this->date,
            'start_time' => $this->start_time,
            'duration' => $this->duration,
            'end_time' => $this->end_time,
            'additional_subject_name' => $this->getConfig('subject_name'),
            'additional_subject_code' => $this->getConfig('subject_code'),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
