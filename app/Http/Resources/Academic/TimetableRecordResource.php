<?php

namespace App\Http\Resources\Academic;

use App\Enums\Day;
use Illuminate\Http\Resources\Json\JsonResource;

class TimetableRecordResource extends JsonResource
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
            'class_timing' => ClassTimingResource::make($this->whenLoaded('classTiming')),
            'day' => $this->day,
            'label' => Day::getLabel($this->day),
            'is_holiday' => $this->is_holiday,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
