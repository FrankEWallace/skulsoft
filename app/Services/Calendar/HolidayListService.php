<?php

namespace App\Services\Calendar;

use App\Contracts\ListGenerator;
use App\Http\Resources\Calendar\HolidayResource;
use App\Models\Calendar\Holiday;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HolidayListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'start_date', 'end_date'];

    protected $defaultSort = 'start_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('calendar.holiday.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'startDate',
                'label' => trans('calendar.holiday.props.start_date'),
                'print_label' => 'start_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'endDate',
                'label' => trans('calendar.holiday.props.end_date'),
                'print_label' => 'end_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'duration',
                'label' => trans('calendar.holiday.props.duration'),
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
        return Holiday::query()
            ->byPeriod()
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return HolidayResource::collection($this->filter($request)
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
