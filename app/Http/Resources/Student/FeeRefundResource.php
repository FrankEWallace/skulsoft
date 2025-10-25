<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Finance\TransactionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeRefundResource extends JsonResource
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
            'date' => $this->date,
            'total' => $this->total,
            'is_cancelled' => $this->is_cancelled,
            'records' => FeeRefundRecordResource::collection($this->whenLoaded('records')),
            'transaction' => TransactionResource::make($this->whenLoaded('transaction')),
            'remarks' => $this->remarks,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
