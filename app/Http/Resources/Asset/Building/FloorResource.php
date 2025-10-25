<?php

namespace App\Http\Resources\Asset\Building;

use Illuminate\Http\Resources\Json\JsonResource;

class FloorResource extends JsonResource
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
            'name_with_block' => $this->block_name.' '.$this->name,
            'block_name' => $this->block_name,
            'block_uuid' => $this->block_uuid,
            'alias' => $this->alias,
            'block' => BlockResource::make($this->whenLoaded('block')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
