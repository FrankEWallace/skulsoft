<?php

namespace App\Http\Resources\Inventory\Report;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemSummaryListResource extends JsonResource
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
            'category_name' => $this->category_name,
            'inventory_name' => $this->inventory_name,
            'opening_balance' => $this->opening_balance + $this->pre_opening_balance,
            'purchased_quantity' => $this->purchased_quantity,
            'returned_quantity' => $this->returned_quantity,
            'adjusted_quantity' => $this->adjusted_quantity,
            'current_balance' => $this->current_balance + $this->pre_opening_balance,
            'created_at' => \Cal::date($this->created_at),
            'updated_at' => \Cal::date($this->updated_at),
        ];
    }
}
