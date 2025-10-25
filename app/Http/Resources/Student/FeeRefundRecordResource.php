<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Finance\FeeHeadResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeRefundRecordResource extends JsonResource
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
            'amount' => $this->amount,
            'head' => FeeHeadResource::make($this->whenLoaded('head')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
