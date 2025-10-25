<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorStatementResource extends JsonResource
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
            'codeNumber' => $this->code_number,
            'date' => \Cal::date($this->date),
            'type' => $this->type,
            'amount' => \Price::from($this->amount),
            'balance' => \Price::from($this->balance),
        ];
    }
}
