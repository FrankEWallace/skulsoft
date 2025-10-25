<?php

namespace App\Services\Site;

use App\Contracts\ListGenerator;
use App\Http\Resources\Site\MenuResource;
use App\Models\Site\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'position'];

    protected $defaultSort = 'position';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('site.menu.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'parent.name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'placement',
                'label' => trans('site.menu.props.placement'),
                'print_label' => 'placement.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'page',
                'label' => trans('site.page.page'),
                'print_label' => 'page.title',
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
        return Menu::query()
            ->with('parent', 'page')
            ->when($request->query('parent'), function ($q) {
                return $q->whereNull('parent_id');
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\ExactMatch:placement',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if ($request->query('all')) {
            return MenuResource::collection($this->filter($request)
                ->orderBy('position', 'asc')
                ->get());
        }

        if ($request->query('menu')) {
            return MenuResource::collection($this->filter($request)
                ->whereHas('parent', function ($q) use ($request) {
                    $q->where('uuid', $request->query('menu'));
                })
                ->orderBy('position', 'asc')
                ->get());
        }

        return MenuResource::collection($this->filter($request)
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
