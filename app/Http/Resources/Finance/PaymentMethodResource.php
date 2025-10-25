<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->getConfig('code'),
            'has_instrument_number' => $this->getConfig('has_instrument_number'),
            'has_instrument_date' => $this->getConfig('has_instrument_date'),
            'has_clearing_date' => $this->getConfig('has_clearing_date'),
            'has_bank_detail' => $this->getConfig('has_bank_detail'),
            'has_reference_number' => $this->getConfig('has_reference_number'),
            'is_payment_gateway' => $this->is_payment_gateway,
            'payment_gateway_name' => $this->payment_gateway_name,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
