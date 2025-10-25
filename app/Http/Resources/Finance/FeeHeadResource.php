<?php

namespace App\Http\Resources\Finance;

use App\Enums\Finance\DefaultCustomFeeType;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeHeadResource extends JsonResource
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
            'group' => FeeGroupResource::make($this->whenLoaded('group')),
            'type' => DefaultCustomFeeType::getDetail($this->type),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
