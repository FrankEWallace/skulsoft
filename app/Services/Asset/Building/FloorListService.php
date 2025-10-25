<?php

namespace App\Services\Asset\Building;

use App\Contracts\ListGenerator;
use App\Http\Resources\Asset\Building\FloorResource;
use App\Models\Asset\Building\Floor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FloorListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'alias'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('asset.building.floor.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'alias',
                'label' => trans('asset.building.floor.props.alias'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'block',
                'label' => trans('asset.building.block.block'),
                'print_label' => 'block.name',
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
        return Floor::query()
            ->withBlock()
            ->notAHostel()
            ->when($request->query('block'), function ($q, $block) {
                $q->where('blocks.uuid', $block);
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name,floors.name',
                'App\QueryFilters\LikeMatch:alias,floors.alias',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return FloorResource::collection($this->filter($request)
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
