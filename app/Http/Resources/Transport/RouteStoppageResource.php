<?php

namespace App\Http\Resources\Transport;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteStoppageResource extends JsonResource
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
            'stoppage' => StoppageResource::make($this->whenLoaded('stoppage')),
            'arrival_time' => $this->arrival_time,
            'position' => $this->position,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
