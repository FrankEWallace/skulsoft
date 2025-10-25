<?php

namespace App\Http\Resources\Hostel;

use App\Http\Resources\Student\StudentSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomAllocationResource extends JsonResource
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
            'room' => RoomResource::make($this->whenLoaded('room')),
            'student' => StudentSummaryResource::make($this->whenLoaded('model')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'period' => $this->period,
            'duration' => $this->duration,
            'remarks' => $this->remarks,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
