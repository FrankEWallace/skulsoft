<?php

namespace App\Http\Resources\Inventory;

use App\Models\Asset\Building\Room;
use App\Models\Inventory\StockBalance;
use Illuminate\Http\Resources\Json\JsonResource;

class StockItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if ($request->show_details) {
            $balances = StockBalance::query()
                ->where('stock_item_id', $this->id)
                ->get();

            $rooms = Room::query()
                ->byTeam()
                ->withFloorAndBlock()
                ->get();
        }

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'category' => StockCategoryResource::make($this->whenLoaded('category')),
            'unit' => $this->unit,
            'quantity' => (float) $this->total_quantity,
            'description' => $this->description,
            'is_quantity_editable' => $this->is_quantity_editable,
            $this->mergeWhen($this->is_quantity_editable, [
                'place' => $this->place,
                'editable_quantity' => $this->quantity,
            ]),
            $this->mergeWhen($request->show_details, [
                'balances' => isset($balances) ? $balances->map(function ($balance) use ($rooms) {
                    $room = $rooms->firstWhere('id', $balance->place_id);

                    return [
                        'room' => $room->full_name,
                        'quantity' => $balance->current_quantity + $balance->opening_quantity,
                    ];
                })
                : [],
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
