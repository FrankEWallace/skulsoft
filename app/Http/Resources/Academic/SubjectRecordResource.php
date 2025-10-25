<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class SubjectRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'subject' => SubjectResource::make($this->whenLoaded('subject')),
            'course' => CourseResource::make($this->whenLoaded('course')),
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            $this->mergeWhen($request->query('details'), [
                'incharge' => SubjectInchargeResource::make($this->whenLoaded('incharge')),
                'incharges' => DivisionInchargeResource::collection($this->whenLoaded('incharges')),
            ]),
            'credit' => $this->credit,
            'max_class_per_week' => $this->max_class_per_week,
            'exam_fee' => $this->exam_fee,
            'course_fee' => $this->course_fee,
            'is_elective' => $this->is_elective,
            'has_no_exam' => $this->has_no_exam,
            'has_grading' => $this->has_grading,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
