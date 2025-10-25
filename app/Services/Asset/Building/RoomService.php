<?php

namespace App\Services\Asset\Building;

use App\Http\Resources\Asset\Building\BlockResource;
use App\Http\Resources\Asset\Building\FloorResource;
use App\Models\Asset\Building\Block;
use App\Models\Asset\Building\Floor;
use App\Models\Asset\Building\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoomService
{
    public function preRequisite(Request $request): array
    {
        $blocks = BlockResource::collection(Block::query()
            ->byTeam()
            ->notAHostel()
            ->get());

        $floors = FloorResource::collection(Floor::query()
            ->withBlock()
            ->notAHostel()
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
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('asset.building.room.room'), 'dependency' => trans('inventory.stock_purchase.stock_purchase')])]);
        }

        $stockAdjustmentExists = \DB::table('stock_adjustments')
            ->wherePlaceType('Room')
            ->wherePlaceId($room->id)
            ->exists();

        if ($stockAdjustmentExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('asset.building.room.room'), 'dependency' => trans('inventory.stock_adjustment.stock_adjustment')])]);
        }

        $stockRequisitionExists = \DB::table('stock_requisitions')
            ->wherePlaceType('Room')
            ->wherePlaceId($room->id)
            ->exists();

        if ($stockRequisitionExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('asset.building.room.room'), 'dependency' => trans('inventory.stock_requisition.stock_requisition')])]);
        }

        return true;
    }
}
