<?php

namespace App\Http\Resources\Library;

use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCopyResource extends JsonResource
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
            'book' => BookResource::make($this->whenLoaded('book')),
            'condition' => OptionResource::make($this->whenLoaded('condition')),
            'addition' => BookAdditionResource::make($this->whenLoaded('addition')),
            'vendor' => $this->vendor,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'room_number' => $this->room_number,
            'rack_number' => $this->rack_number,
            'shelf_number' => $this->shelf_number,
            'location' => $this->location,
            'price' => $this->price,
            'number' => $this->number,
            'remarks' => $this->remarks,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
