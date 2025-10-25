<?php

namespace App\Http\Resources\Student;

use App\Enums\Finance\PaymentStatus;
use App\Http\Resources\Finance\FeeConcessionResource;
use App\Http\Resources\Finance\FeeInstallmentResource;
use App\Http\Resources\Transport\CircleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeResource extends JsonResource
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
            'direction' => $this->transport_direction,
            'installment' => FeeInstallmentResource::make($this->whenLoaded('installment')),
            'transport_circle' => CircleResource::make($this->whenLoaded('transportCircle')),
            'concession' => FeeConcessionResource::make($this->whenLoaded('concession')),
            'records' => FeeRecordResource::collection($this->whenLoaded('records')),
            'status' => PaymentStatus::getDetail($this->getStatus()),
            'due_date' => $this->getDueDate(),
            'overdue' => $this->getOverdueDays(),
            'late_fee' => $this->getLateFeeDetail(),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
