<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AudienceResource extends JsonResource
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
            'uuid' => $this->audienceable->uuid,
            'name' => $this->getName(),
            'detail' => $this->getDetail(),
            'type' => $this->getType(),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getName()
    {
        if ($this->audienceable_type == 'Batch') {
            return $this->audienceable->course->name.' '.$this->audienceable->name;
        }

        if ($this->audienceable_type == 'Student') {
            return $this->audienceable?->contact->name;
        }

        if ($this->audienceable_type == 'Employee') {
            return $this->audienceable?->contact->name;
        }

        return $this->audienceable->name;
    }

    private function getDetail()
    {
        if ($this->audienceable_type == 'Student') {
            return $this->audienceable->contact->father_name;
        }

        if ($this->audienceable_type == 'Employee') {
            return $this->audienceable->code_number;
        }

    }

    private function getType()
    {
        if (in_array($this->audienceable_type, ['Batch', 'Course', 'Division', 'Student'])) {
            return 'student';
        }

        return 'employee';
    }
}
