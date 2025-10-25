<?php

namespace App\Services;

use App\Contracts\ListGenerator;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class UserListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'email', 'username'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('user.props.name'),
                'sortable' => true,
                'print_label' => 'profile.name',
                'visibility' => true,
            ],
            [
                'key' => 'email',
                'label' => trans('user.props.email'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'username',
                'label' => trans('user.props.username'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'roles',
                'label' => trans('team.config.role.role'),
                'sortable' => false,
                'type' => 'array',
                'print_key' => 'label',
                'visibility' => true,
            ],
        ];

        if (request()->boolean('with_direct_permission')) {
            $headers[] = [
                'key' => 'permissions',
                'label' => trans('team.config.permission.permission'),
                'print_label' => 'permissions',
                'sortable' => false,
                'visibility' => true,
            ];
        }

        $headers[] = [
            'key' => 'status',
            'label' => trans('user.status'),
            'print_label' => 'status_detail.label',
            'sortable' => false,
            'visibility' => true,
        ];

        $headers[] = [
            'key' => 'createdAt',
            'label' => trans('general.created_at'),
            'print_label' => 'created_at.formatted',
            'sortable' => true,
            'visibility' => true,
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $roles = Str::toArray($request->query('roles'));
        $withDirectPermission = $request->boolean('with_direct_permission');

        return User::query()
            ->with('roles')
            ->when($withDirectPermission, function ($q) {
                $q->has('permissions', '>', 0)
                    ->with('permissions');
            })
            ->when(! \Auth::user()->is_default, function ($q) {
                $q->whereHas('roles', function ($q1) {
                    $q1->where('model_has_roles.team_id', auth()->user()?->current_team_id);
                });
            })
            ->isNotAdmin()
            ->when($roles, function ($q) use ($roles) {
                $q->whereHas('roles', function ($q1) use ($roles) {
                    $q1->whereIn('roles.uuid', $roles);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:email',
                'App\QueryFilters\LikeMatch:username',
                'App\QueryFilters\ExactMatch:status',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $statuses = $this->filter($request)->toBase()
            ->selectRaw("count(case when status = 'activated' then 1 end) as activated")
            ->selectRaw("count(case when status = 'banned' then 1 end) as banned")
            ->selectRaw("count(case when status = 'disapproved' then 1 end) as disapproved")
            ->selectRaw("count(case when status = 'pending_verification' then 1 end) as pending_verification")
            ->selectRaw("count(case when status = 'pending_approval' then 1 end) as pending_approval")
            ->first();

        return UserResource::collection($this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                    'statuses' => $statuses,
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
