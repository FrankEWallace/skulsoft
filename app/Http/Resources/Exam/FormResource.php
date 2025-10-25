<?php

namespace App\Http\Resources\Exam;

use App\Http\Resources\Student\StudentSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
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
            'schedule' => ScheduleResource::make($this->whenLoaded('schedule')),
            'student' => StudentSummaryResource::make($this->whenLoaded('student')),
            'confirmed_at' => $this->confirmed_at,
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
