<?php

namespace App\Http\Resources\Transport\Vehicle;

use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRecordResource extends JsonResource
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
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle')),
            'date' => $this->date,
            'log' => $this->log,
            'amount' => $this->amount,
            'next_due_date' => $this->next_due_date,
            'next_due_log' => $this->next_due_log,
            'log' => $this->log,
            'remarks' => $this->remarks,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
