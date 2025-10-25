<?php

namespace App\Http\Resources\Asset\Building;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
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
            'number' => $this->number,
            'full_name' => $this->name.' '.$this->block_name.' '.$this->floor_name,
            'floor_name' => $this->floor_name,
            'floor_name_with_block' => $this->block_name.' '.$this->floor_name,
            'floor_uuid' => $this->floor_uuid,
            'block_name' => $this->block_name,
            'block_uuid' => $this->block_uuid,
            'floor' => FloorResource::make($this->whenLoaded('floor')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
