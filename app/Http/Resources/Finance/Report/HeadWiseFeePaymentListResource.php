<?php

namespace App\Http\Resources\Finance\Report;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class HeadWiseFeePaymentListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [];
        $data['registration_fee'] = \Price::from($this->registrationFee);
        foreach ($request->fee_head_slugs as $feeHeadSlug) {
            $feeHead = Str::camel($feeHeadSlug);
            $data[$feeHead] = \Price::from($this->$feeHead);
        }

        $data['transport_fee'] = \Price::from($this->transportFee);
        $data['late_fee'] = \Price::from($this->lateFee);
        $data['additional_charge'] = \Price::from($this->additionalCharge);
        $data['additional_discount'] = \Price::from($this->additionalDiscount);

        return [
            'date' => $this->date,
            'fee_heads' => $data,
            'total' => \Price::from($this->total),
        ];
    }
}
