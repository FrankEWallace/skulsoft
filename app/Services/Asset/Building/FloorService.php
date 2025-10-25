<?php

namespace App\Services\Asset\Building;

use App\Http\Resources\Asset\Building\BlockResource;
use App\Models\Asset\Building\Block;
use App\Models\Asset\Building\Floor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FloorService
{
    public function preRequisite(Request $request): array
    {
        $blocks = BlockResource::collection(Block::query()
            ->byTeam()
            ->notAHostel()
            ->get());

        return compact('blocks');
    }

    public function create(Request $request): Floor
    {
        \DB::beginTransaction();

        $floor = Floor::forceCreate($this->formatParams($request));

        \DB::commit();

        return $floor;
    }

    private function formatParams(Request $request, ?Floor $floor = null): array
    {
        $formatted = [
            'name' => $request->name,
            'alias' => $request->alias,
            'block_id' => $request->block_id,
            'description' => $request->description,
        ];

        if (! $floor) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Floor $floor): void
    {
        \DB::beginTransaction();

        $floor->forceFill($this->formatParams($request, $floor))->save();

        \DB::commit();
    }

    public function deletable(Floor $floor): bool
    {
        $roomExists = \DB::table('rooms')
            ->whereFloorId($floor->id)
            ->exists();

        if ($roomExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('asset.building.floor.floor'), 'dependency' => trans('asset.building.room.room')])]);
        }

        return true;
    }
}
