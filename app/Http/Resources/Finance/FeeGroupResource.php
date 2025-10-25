<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\Academic\PeriodResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeGroupResource extends JsonResource
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
            'heads' => FeeHeadResource::collection($this->whenLoaded('heads')),
            'pg_account' => $this->getMeta('pg_account'),
            'description' => $this->description,
            'is_custom' => (bool) $this->getMeta('is_custom'),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
