<?php

namespace App\Http\Resources\Inventory;

use App\Http\Resources\Asset\Building\RoomResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
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
            'inventory' => InventoryResource::make($this->whenLoaded('inventory')),
            'from_place' => RoomResource::make($this->whenLoaded('from')),
            'to_place' => RoomResource::make($this->whenLoaded('to')),
            'items' => StockItemRecordResource::collection($this->whenLoaded('items')),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
