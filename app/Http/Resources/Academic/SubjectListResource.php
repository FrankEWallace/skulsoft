<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class SubjectListResource extends JsonResource
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
            'name' => $this->name,
            'alias' => $this->alias,
            'code' => $this->code,
            'shortcode' => $this->shortcode,
            'position' => $this->position,
            'subject_record_position' => $this->subject_record_position,
            'description' => $this->description,
            'max_class_per_week' => $this->max_class_per_week,
            'is_elective' => (bool) $this->is_elective,
            'has_no_exam' => (bool) $this->has_no_exam,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
