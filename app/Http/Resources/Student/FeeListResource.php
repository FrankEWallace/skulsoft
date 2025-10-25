<?php

namespace App\Http\Resources\Student;

use App\Enums\Finance\PaymentStatus;
use App\Http\Resources\Finance\FeeConcessionResource;
use App\Http\Resources\Finance\FeeInstallmentResource;
use App\Http\Resources\Transport\CircleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeListResource extends JsonResource
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
            'total' => $this->getTotal($request->query('date')),
            'balance' => $this->getBalance($request->query('date')),
            'paid' => $this->getPaid(),
            'status' => PaymentStatus::getDetail($this->getStatus($request->query('date'))),
            $this->mergeWhen($request->has_bank_transfer, [
                'bank_transfers' => $this->bank_transfers,
            ]),
            'has_custom_concession' => (bool) $this->getMeta('has_custom_concession'),
            'custom_concession_remarks' => $this->getMeta('custom_concession_remarks'),
            'due_date' => $this->getDueDate(),
            'overdue' => $this->getOverdueDays(),
            'late_fee' => $this->getLateFeeDetail($request->query('date')),
            'original_late_fee_amount' => \Price::from($this->getMeta('original_late_fee_amount')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
