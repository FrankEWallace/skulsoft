<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Finance\TransactionPaymentResource;
use App\Http\Resources\Finance\TransactionRecordResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class TransactionListResource extends JsonResource
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
            'code_number' => $this->code_number,
            'user_name' => $this->user_name ?? trans('auth.guest'),
            'date' => $this->date,
            $this->mergeWhen($this->is_online, [
                'is_online' => true,
                'is_failed' => ! $this->processed_at->value ? true : false,
                'reference_number' => Arr::get($this->payment_gateway, 'reference_number'),
                'gateway' => Arr::get($this->payment_gateway, 'name'),
            ]),
            'payments' => TransactionPaymentResource::collection($this->payments),
            'records' => TransactionRecordResource::collection($this->records),
            'cancelled_at' => $this->cancelled_at,
            'is_cancelled' => $this->cancelled_at->value ? true : false,
            'is_rejected' => $this->rejected_at->value ? true : false,
            'rejected_at' => $this->rejected_at,
            'is_editable' => $this->isFeeReceiptEditable(),
            $this->mergeWhen($request->validate_clearance, [
                'pending_clearance' => $this->pending_clearance,
            ]),
            'design' => $this->getDesign(),
            'remarks' => $this->remarks,
            'cancellation_remarks' => $this->cancellation_remarks,
            $this->mergeWhen($this->rejected_at->value, [
                'rejected_date' => \Cal::date(Arr::get($this->rejection_record, 'rejected_date', $this->rejected_at->value)),
                'rejection_charge' => \Price::from(Arr::get($this->rejection_record, 'rejection_charge', 0)),
                'rejection_remarks' => $this->rejection_remarks,
            ]),
            'created_at' => \Cal::datetime($this->created_at),
            'updated_at' => \Cal::datetime($this->updated_at),
        ];
    }

    private function getDesign()
    {
        if ($this->cancelled_at->value) {
            return 'danger';
        } elseif ($this->rejected_at->value) {
            return 'warning';
        } elseif ($this->is_online && ! $this->processed_at->value) {
            return 'danger';
        } elseif ($this->pending_clearance) {
            return 'warning';
        }

        return 'success';
    }
}
