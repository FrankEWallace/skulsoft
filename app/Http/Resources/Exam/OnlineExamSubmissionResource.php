<?php

namespace App\Http\Resources\Exam;

use Illuminate\Http\Resources\Json\JsonResource;

class OnlineExamSubmissionResource extends JsonResource
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
            'student_name' => $this->student_name,
            'admission_number' => $this->admission_number,
            'batch_name' => $this->batch_name,
            'course_name' => $this->course_name,
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'evaluated_at' => $this->evaluated_at,
            'obtained_mark' => $this->obtained_mark,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
