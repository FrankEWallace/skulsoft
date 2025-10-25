<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'sno' => $this->sno,
            'code_number' => $this->code_number,
            'roll_number' => $this->roll_number,
            'name' => $this->name,
            'joining_date' => \Cal::date($this->joining_date),
            'leaving_date' => \Cal::date($this->leaving_date),
            'batch_uuid' => $this->batch_uuid,
            'course_uuid' => $this->course_uuid,
            'batch_name' => $this->batch_name,
            'course_name' => $this->course_name,
            'photo' => $this->photo_url,
            'start_date' => \Cal::date($this->start_date),
            'end_date' => \Cal::date($this->end_date),
            $this->mergeWhen($this->list_attendance, [
                'summary' => $this->summary,
                'attendance' => $this->attendance,
                'attendances' => $this->attendances,
            ]),
            $this->mergeWhen($this->list_summary, [
                'summary' => $this->summary,
                'additional_summary' => $this->additional_summary,
            ]),
        ];
    }
}
