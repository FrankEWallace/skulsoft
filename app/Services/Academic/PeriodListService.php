<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\PeriodResource;
use App\Models\Academic\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PeriodListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'start_date', 'end_date'];

    protected $defaultSort = 'created_at';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('academic.period.period'),
                'print_label' => 'name',
                'print_sub_label' => 'alias',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'registration',
                'label' => trans('student.registration.registration'),
                'print_label' => 'enable_registration',
                'type' => 'boolean',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'session',
                'label' => trans('academic.session.session'),
                'print_label' => 'session.code',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'code',
                'label' => trans('academic.period.props.code'),
                'print_label' => 'code',
                'print_sub_label' => 'shortcode',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'startDate',
                'label' => trans('academic.period.props.start_date'),
                'print_label' => 'start_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'endDate',
                'label' => trans('academic.period.props.end_date'),
                'print_label' => 'end_date.formatted',
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
        return Period::query()
            ->with('session')
            ->byTeam()
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:code',
                'App\QueryFilters\LikeMatch:shortcode',
                'App\QueryFilters\LikeMatch:alias',
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return PeriodResource::collection($this->filter($request)
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
