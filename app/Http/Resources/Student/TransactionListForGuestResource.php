<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Finance\TransactionRecordResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class TransactionListForGuestResource extends JsonResource
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
            'date' => $this->date,
            $this->mergeWhen($this->is_online, [
                'is_online' => true,
                'is_failed' => ! $this->processed_at->value ? true : false,
                'reference_number' => Arr::get($this->payment_gateway, 'reference_number'),
                'gateway' => Arr::get($this->payment_gateway, 'name'),
            ]),
            'records' => TransactionRecordResource::collection($this->records),
            'cancelled_at' => $this->cancelled_at,
            'is_cancelled' => $this->cancelled_at->value ? true : false,
            'is_rejected' => $this->rejected_at->value ? true : false,
            'rejected_at' => $this->rejected_at,
            'design' => $this->getDesign(),
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
        }

        return 'success';
    }
}
