<?php

namespace App\Services\Hostel;

use App\Http\Resources\Hostel\BlockResource;
use App\Http\Resources\Hostel\FloorResource;
use App\Models\Hostel\Block;
use App\Models\Hostel\Floor;
use App\Models\Hostel\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoomService
{
    public function preRequisite(Request $request): array
    {
        $blocks = BlockResource::collection(Block::query()
            ->byTeam()
            ->hostel()
            ->get());

        $floors = FloorResource::collection(Floor::query()
            ->withBlock()
            ->hostel()
            ->get());

        return compact('floors', 'blocks');
    }

    public function create(Request $request): Room
    {
        \DB::beginTransaction();

        $room = Room::forceCreate($this->formatParams($request));

        \DB::commit();

        return $room;
    }

    private function formatParams(Request $request, ?Room $room = null): array
    {
        $formatted = [
            'name' => $request->name,
            'number' => $request->number,
            'floor_id' => $request->floor_id,
            'capacity' => $request->capacity,
            'description' => $request->description,
        ];

        if (! $room) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Room $room): void
    {
        \DB::beginTransaction();

        $room->forceFill($this->formatParams($request, $room))->save();

        \DB::commit();
    }

    public function deletable(Room $room): bool
    {
        $stockPurchaseExists = \DB::table('stock_purchases')
            ->wherePlaceType('Room')
            ->wherePlaceId($room->id)
            ->exists();

        if ($stockPurchaseExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('hostel.room.room'), 'dependency' => trans('inventory.stock_purchase.stock_purchase')])]);
        }

        $stockAdjustmentExists = \DB::table('stock_adjustments')
            ->wherePlaceType('Room')
            ->wherePlaceId($room->id)
            ->exists();

        if ($stockAdjustmentExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('hostel.room.room'), 'dependency' => trans('inventory.stock_adjustment.stock_adjustment')])]);
        }

        $stockRequisitionExists = \DB::table('stock_requisitions')
            ->wherePlaceType('Room')
            ->wherePlaceId($room->id)
            ->exists();

        if ($stockRequisitionExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('hostel.room.room'), 'dependency' => trans('inventory.stock_requisition.stock_requisition')])]);
        }

        return true;
    }
}
