<?php

namespace App\Http\Resources\Student;

use App\Enums\Finance\DefaultFeeHead;
use App\Http\Resources\Finance\FeeHeadResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeRecordResource extends JsonResource
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
            'head' => FeeHeadResource::make($this->whenLoaded('head')),
            'default_fee_head' => DefaultFeeHead::getDetail($this->default_fee_head),
            'fee_head_name' => $this->fee_head_id ? $this->head->name : DefaultFeeHead::getLabel($this->default_fee_head->value),
            'amount' => $this->amount,
            'amount_with_concession' => $this->getAmountWithConcession(),
            'paid' => $this->paid,
            'concession' => $this->concession,
            'has_custom_amount' => (bool) $this->has_custom_amount,
            'due_date' => $this->due_date,
            'is_optional' => (bool) $this->is_optional,
            'remarks' => $this->remarks,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
