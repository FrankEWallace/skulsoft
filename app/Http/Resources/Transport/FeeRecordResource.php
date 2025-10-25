<?php

namespace App\Http\Resources\Transport;

use Illuminate\Http\Resources\Json\JsonResource;

class FeeRecordResource extends JsonResource
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
            'arrival_amount' => $this->arrival_amount,
            'departure_amount' => $this->departure_amount,
            'roundtrip_amount' => $this->roundtrip_amount,
            'circle' => CircleResource::make($this->whenLoaded('circle')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
