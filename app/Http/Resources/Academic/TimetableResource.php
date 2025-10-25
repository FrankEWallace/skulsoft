<?php

namespace App\Http\Resources\Academic;

use App\Http\Resources\Asset\Building\RoomResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TimetableResource extends JsonResource
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
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            'room' => RoomResource::make($this->whenLoaded('room')),
            'effective_date' => $this->effective_date,
            'records' => TimetableRecordResource::collection($this->whenLoaded('records')),
            $this->mergeWhen($this->has_detail, [
                'days' => $this->days,
            ]),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
