<?php

namespace App\Services\Utility;

use App\Contracts\ListGenerator;
use App\Http\Resources\Utility\ActivityResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pipeline\Pipeline;
use Spatie\Activitylog\Models\Activity;

class ActivityLogListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'user',
                'label' => trans('user.user'),
                'print_label' => 'user.profile.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'activity',
                'label' => trans('utility.activity.activity'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'ip',
                'label' => trans('utility.activity.ip'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'browser',
                'label' => trans('utility.activity.browser'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'os',
                'label' => trans('utility.activity.os'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'createdAt',
                'label' => trans('utility.activity.date_time'),
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $query = Activity::query()
            ->when(! \Auth::user()->hasRole('admin'), function ($q) {
                $q->whereUserId(\Auth::id());
            });

        return app(Pipeline::class)
            ->send($query)
            ->through([
                'App\QueryFilters\LikeMatch:search,log_name,description',
                'App\QueryFilters\DateBetween:start_date,end_date,created_at,datetime',
            ])->thenReturn();
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return ActivityResource::collection($this->filter($request)
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
