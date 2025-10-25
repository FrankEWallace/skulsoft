<?php

namespace App\Http\Resources\Academic;

use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
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
            'alias' => $this->alias,
            'code' => $this->code,
            'shortcode' => $this->shortcode,
            'type' => OptionResource::make($this->whenLoaded('type')),
            'position' => $this->position,
            'period' => new PeriodResource($this->whenLoaded('period')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
