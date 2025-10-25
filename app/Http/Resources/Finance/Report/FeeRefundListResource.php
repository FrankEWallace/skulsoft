<?php

namespace App\Http\Resources\Finance\Report;

use App\Http\Resources\Finance\TransactionPaymentResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class FeeRefundListResource extends JsonResource
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
            'student_uuid' => $this->student_uuid,
            'voucher_number' => $this->voucher_number,
            'amount' => $this->amount,
            'date' => $this->date,
            'ledger_name' => $this->ledger_name,
            'ledger_type' => $this->ledger_type,
            'type' => $this->type,
            'name' => $this->name,
            'father_name' => $this->father_name,
            'code_number' => $this->code_number,
            'joining_date' => \Cal::date($this->joining_date),
            'payment' => TransactionPaymentResource::make($this->whenLoaded('payment')),
            'batch_name' => $this->batch_name,
            'course_name' => $this->course_name,
            'contact_number' => $this->contact_number,
            $this->mergeWhen($this->is_online, [
                'is_online' => true,
                'reference_number' => Arr::get($this->payment_gateway, 'reference_number'),
                'gateway' => Arr::get($this->payment_gateway, 'name'),
            ]),
        ];
    }
}
