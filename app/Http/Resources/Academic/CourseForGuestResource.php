<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseForGuestResource extends JsonResource
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
            'name_with_term' => $this->name_with_term,
            'position' => $this->position,
            'enable_registration' => $this->enable_registration,
            'registration_fee' => $this->registration_fee,
        ];
    }
}
