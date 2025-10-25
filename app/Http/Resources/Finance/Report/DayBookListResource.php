<?php

namespace App\Http\Resources\Finance\Report;

use App\Enums\Finance\TransactionType;
use App\Http\Resources\Finance\TransactionPaymentResource;
use App\Http\Resources\Finance\TransactionRecordResource;
use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class DayBookListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $transactionable = null;
        if ($this->transactionable_type == 'Student') {
            $student = $request->students->firstWhere('id', $this->transactionable_id);
            $transactionable = [
                'name' => $student?->name,
                'detail' => $student?->code_number,
                'sub_detail' => $student?->course_name . ' ' . $student?->batch_name,
            ];
        } else {
            $transactionable = [
                'name' => $this->transactionable?->contact?->name,
                'detail' => $this->transactionable?->contact?->contact_number,
            ];
        }

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'date' => $this->date,
            'amount' => $this->amount,
            $this->mergeWhen($this->transactionable_type, [
                'transactionable' => $transactionable,
            ]),
            $this->mergeWhen($this->is_online, [
                'is_online' => (bool) $this->is_online,
                'is_completed' => $this->processed_at->value ? true : false,
                'gateway' => Arr::get($this->payment_gateway, 'name'),
                'reference_number' => Arr::get($this->payment_gateway, 'reference_number'),
            ]),
            'payment' => $this->type == TransactionType::PAYMENT ? $this->amount : null,
            'receipt' => $this->type == TransactionType::RECEIPT ? $this->amount : null,
            'payment' => TransactionPaymentResource::make($this->whenLoaded('payment')),
            'payments' => TransactionPaymentResource::collection($this->whenLoaded('payments')),
            'records' => TransactionRecordResource::collection($this->whenLoaded('records')),
            'record' => TransactionRecordResource::make($this->whenLoaded('record')),
            'user' => UserSummaryResource::make($this->whenLoaded('user')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
