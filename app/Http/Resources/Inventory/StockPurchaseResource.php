<?php

namespace App\Http\Resources\Inventory;

use App\Http\Resources\Asset\Building\RoomResource;
use App\Http\Resources\Finance\LedgerResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class StockPurchaseResource extends JsonResource
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
            'code_number' => $this->code_number,
            'date' => $this->date,
            'voucher_number' => $this->voucher_number,
            'total' => $this->total,
            'has_items' => $this->has_items,
            'inventory' => InventoryResource::make($this->whenLoaded('inventory')),
            'vendor' => LedgerResource::make($this->whenLoaded('vendor')),
            'place' => RoomResource::make($this->whenLoaded('place')),
            'items' => StockItemRecordResource::collection($this->whenLoaded('items')),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
