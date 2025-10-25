<?php

namespace App\Services;

use App\Contracts\ListGenerator;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OptionListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('option.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'details',
                'label' => '',
                'print_label' => 'something',
                'printable' => false,
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'description',
                'label' => trans('option.props.description'),
                'sortable' => true,
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
        return Option::query()
            ->when($request->team, function ($q) {
                $q->whereTeamId(auth()->user()?->current_team_id);
            })
            ->whereType($request->query('type'))
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\LikeMatch:name',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return OptionResource::collection($this->filter($request)
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
