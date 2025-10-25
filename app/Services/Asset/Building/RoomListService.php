<?php

namespace App\Services\Asset\Building;

use App\Contracts\ListGenerator;
use App\Http\Resources\Asset\Building\RoomResource;
use App\Models\Asset\Building\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('asset.building.room.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'number',
                'label' => trans('asset.building.room.props.number'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'floor',
                'label' => trans('asset.building.floor.floor'),
                'print_label' => 'floor.name_with_block',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'createdAt',
                'label' => trans('general.created_at'),
                'print_label' => 'created_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $blockFloorRoom = $request->query('block_floor_room');

        return Room::query()
            ->withFloorAndBlock()
            ->notAHostel()
            ->when($request->query('block'), function ($q, $block) {
                $q->where('blocks.uuid', $block);
            })
            ->when($request->query('floor'), function ($q, $floor) {
                $q->where('floors.uuid', $floor);
            })
            ->when($blockFloorRoom, function ($q, $blockFloorRoom) {
                $q->where('rooms.name', 'like', "%{$blockFloorRoom}%")
                    ->orWhere('floors.name', 'like', "%{$blockFloorRoom}%")
                    ->orWhere('blocks.name', 'like', "%{$blockFloorRoom}%");
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name,rooms.name',
                'App\QueryFilters\LikeMatch:number,rooms.number',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return RoomResource::collection($this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
