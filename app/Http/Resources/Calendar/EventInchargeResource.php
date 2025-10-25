<?php

namespace App\Http\Resources\Calendar;

use App\Http\Resources\Employee\EmployeeSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EventInchargeResource extends JsonResource
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
            'event' => EventResource::make($this->whenLoaded('model')),
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
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
