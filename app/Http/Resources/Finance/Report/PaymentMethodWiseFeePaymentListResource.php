<?php

namespace App\Http\Resources\Finance\Report;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PaymentMethodWiseFeePaymentListResource extends JsonResource
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
        foreach ($request->payment_method_slugs as $paymentMethodSlug) {
            $paymentMethod = Str::camel($paymentMethodSlug);
            $data[$paymentMethod] = \Price::from($this->$paymentMethod);
        }

        return [
            'date' => $this->date,
            'payment_methods' => $data,
            'total' => \Price::from($this->total),
        ];
    }
}
