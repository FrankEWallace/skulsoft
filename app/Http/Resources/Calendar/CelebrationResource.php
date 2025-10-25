<?php

namespace App\Http\Resources\Calendar;

use App\Enums\Gender;
use Illuminate\Http\Resources\Json\JsonResource;

class CelebrationResource extends JsonResource
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
            'name' => $this->name,
            'code_number' => $this->code_number,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'contact_number' => $this->contact_number,
            'gender' => Gender::getDetail($this->gender),
            'joining_date' => \Cal::date($this->joining_date),
            'birth_date' => \Cal::date($this->birth_date),
            'batch_uuid' => $this->batch_uuid,
            'batch_name' => $this->batch_name,
            'course_uuid' => $this->course_uuid,
            'course_name' => $this->course_name,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
