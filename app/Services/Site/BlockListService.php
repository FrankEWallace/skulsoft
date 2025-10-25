<?php

namespace App\Services\Site;

use App\Contracts\ListGenerator;
use App\Http\Resources\Site\BlockResource;
use App\Models\Site\Block;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlockListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'title', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('site.block.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'title',
                'label' => '',
                'print_label' => 'title',
                'print_sub_title' => 'sub_title',
                'sortable' => false,
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
        return Block::query()
            ->with('menu')
            ->when($request->query('type') === 'slider', function ($query) {
                $query->where('meta->is_slider', true);
            })
            ->filter([
                'App\QueryFilters\LikeMatch:search,title,sub_title',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if ($request->query('all')) {
            return BlockResource::collection($this->filter($request)
                ->orderBy('position', 'asc')
                ->get());
        }

        return BlockResource::collection($this->filter($request)
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
