<?php

namespace App\Services\Auth;

use App\Contracts\ListGenerator;
use App\Http\Resources\Auth\FailedLoginAttemptResource;
use App\Models\FailedLoginAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FailedLoginAttemptListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'email'];

    protected $defaultSort = 'created_at';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'email',
                'label' => trans('auth.login.props.email_or_username'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'ip',
                'label' => trans('utility.activity.ip'),
                'sortable' => true,
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
        return FailedLoginAttempt::query()
            ->when($request->query('ip'), fn ($query, $ip) => $query->where('meta->ip', $ip))
            ->filter([
                'App\QueryFilters\LikeMatch:email',
                'App\QueryFilters\DateBetween:start_date,end_date,created_at',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return FailedLoginAttemptResource::collection($this->filter($request)
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
