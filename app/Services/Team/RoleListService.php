<?php

namespace App\Services\Team;

use App\Contracts\ListGenerator;
use App\Http\Resources\Team\RoleResource;
use App\Models\Team;
use App\Models\Team\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('team.config.role.props.name'),
                'print_label' => 'label',
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

    public function filter(Request $request, Team $team): Builder
    {
        return Role::query()
            ->whereTeamId($team->id)
            ->filter([
                'App\QueryFilters\LikeMatch:search,name',
            ]);
    }

    public function paginate(Request $request, Team $team): AnonymousResourceCollection
    {
        return RoleResource::collection($this->filter($request, $team)
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

    public function list(Request $request, Team $team): AnonymousResourceCollection
    {
        return $this->paginate($request, $team);
    }
}
