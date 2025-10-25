<?php

namespace App\Services\Activity;

use App\Contracts\ListGenerator;
use App\Http\Resources\Activity\TripResource;
use App\Models\Activity\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TripListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'start_date'];

    protected $defaultSort = 'start_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'type',
                'label' => trans('activity.trip.props.type'),
                'print_label' => 'type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'title',
                'label' => trans('activity.trip.props.title'),
                'print_label' => 'title',
                'print_sub_label' => 'venue',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'startDate',
                'label' => trans('activity.trip.props.start_date'),
                'print_label' => 'start_date.formatted',
                'print_sub_label' => 'start_time.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'endDate',
                'label' => trans('activity.trip.props.end_date'),
                'print_label' => 'end_date.formatted',
                'print_sub_label' => 'end_time.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'fee',
                'label' => trans('activity.trip.props.fee'),
                'print_label' => 'fee.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'audience',
                'label' => trans('activity.trip.props.audience'),
                'type' => 'array',
                'print_label' => 'audience_types',
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
        $types = Str::toArray($request->query('types'));

        return Trip::query()
            ->byPeriod()
            ->filterAccessible()
            ->with('type')
            ->when($types, function ($q, $types) {
                $q->whereHas('type', function ($q) use ($types) {
                    $q->whereIn('uuid', $types);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:search,title,description',
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return TripResource::collection($this->filter($request)
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
