<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\Academic\PeriodResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class FeeConcessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $transportConcessionType = Arr::get($this->transport, 'type', 'percent');

        if ($transportConcessionType == 'amount') {
            $transportConcessionValue = \Price::from(Arr::get($this->transport, 'value', 0));
        } else {
            $transportConcessionValue = \Percent::from(Arr::get($this->transport, 'value', 0));
        }

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'period' => PeriodResource::make($this->whenLoaded('period')),
            'records' => FeeConcessionRecordResource::collection($this->whenLoaded('records')),
            'transport_type' => $transportConcessionType,
            'transport_value' => $transportConcessionValue,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
