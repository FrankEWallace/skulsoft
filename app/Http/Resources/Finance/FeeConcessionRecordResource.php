<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Resources\Json\JsonResource;

class FeeConcessionRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $type = $this->type;

        if ($type == 'amount') {
            $value = \Price::from($this->value);
        } else {
            $value = \Percent::from($this->value);
        }

        return [
            'uuid' => $this->uuid,
            'head' => FeeHeadResource::make($this->whenLoaded('head')),
            'type' => $type,
            'value' => $value,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
