<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class TransactionRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $additionalCharges = collect($this->getMeta('additional_charges') ?? [])->map(function ($charge) {
            return [
                'label' => Arr::get($charge, 'label'),
                'amount' => \Price::from(Arr::get($charge, 'amount')),
            ];
        });

        $additionalDiscounts = collect($this->getMeta('additional_discounts') ?? [])->map(function ($discount) {
            return [
                'label' => Arr::get($discount, 'label'),
                'amount' => \Price::from(Arr::get($discount, 'amount')),
            ];
        });

        return [
            'uuid' => $this->uuid,
            'amount' => $this->amount,
            'student_fee_uuid' => $this->student_fee_uuid,
            'ledger' => LedgerResource::make($this->whenLoaded('ledger')),
            'direction' => $this->direction,
            'additional_charges' => $additionalCharges,
            'additional_discounts' => $additionalDiscounts,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
