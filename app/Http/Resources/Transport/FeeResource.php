<?php

namespace App\Http\Resources\Transport;

use App\Http\Resources\Academic\PeriodResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeResource extends JsonResource
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
            'name' => $this->name,
            'period' => PeriodResource::make($this->whenLoaded('period')),
            'records' => FeeRecordResource::collection($this->whenLoaded('records')),
            'is_assigned' => $this->is_assigned,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
