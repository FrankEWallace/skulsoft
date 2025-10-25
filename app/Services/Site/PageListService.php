<?php

namespace App\Services\Site;

use App\Contracts\ListGenerator;
use App\Http\Resources\Site\PageSummaryResource;
use App\Models\Site\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'title'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('site.page.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'title',
                'label' => trans('site.page.props.title'),
                'print_label' => 'title',
                'print_sub_label' => 'sub_title',
                'sortable' => true,
                'visibility' => true,
            ],
            // [
            //     'key' => 'status',
            //     'label' => '',
            //     'print_label' => 'status',
            //     'sortable' => false,
            //     'visibility' => true,
            // ],
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
        return Page::query()
            ->select('id', 'uuid', 'name', 'title', 'sub_title', 'created_at', 'updated_at')
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\LikeMatch:search,title,sub_title',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return PageSummaryResource::collection($this->filter($request)
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
