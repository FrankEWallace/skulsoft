<?php

namespace App\Http\Resources\Academic;

use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentSeatResource extends JsonResource
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
            'course' => CourseResource::make($this->whenLoaded('course')),
            'enrollment_type' => OptionResource::make($this->whenLoaded('enrollmentType')),
            'position' => $this->position,
            'max_seat' => $this->max_seat,
            'booked_seat' => $this->booked_seat,
            'available_seat' => $this->max_seat - $this->booked_seat,
            'seat' => $this->booked_seat.'/'.$this->max_seat,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
