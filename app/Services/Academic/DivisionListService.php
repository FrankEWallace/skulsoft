<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\DivisionResource;
use App\Models\Academic\Division;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DivisionListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'start_date', 'end_date'];

    protected $defaultSort = 'created_at';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('academic.division.division'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'code',
                'label' => trans('academic.division.props.code'),
                'print_label' => 'code',
                'print_sub_label' => 'shortcode',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'program',
                'label' => trans('academic.program.program'),
                'print_label' => 'program.name',
                'print_sub_label' => 'program.department.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'incharge',
                'label' => trans('academic.division_incharge.division_incharge'),
                'print_label' => 'incharges',
                'print_key' => 'employee.name',
                'print_sub_key' => 'period',
                'type' => 'array',
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
        $details = $request->query('details');

        return Division::query()
            ->with('program.department')
            ->byPeriod()
            ->filterAccessible()
            ->when($details, function ($q) {
                $q->withCurrentIncharges();
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:code',
                'App\QueryFilters\LikeMatch:shortcode',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if ($request->query('all')) {
            return DivisionResource::collection($this->filter($request)
                ->orderBy('position', 'asc')
                ->get());
        }

        $query = $this->filter($request);

        if (! $request->query('sort')) {
            $query->orderBy('divisions.position', 'asc');
        } else {
            $query->orderBy($this->getSort(), $this->getOrder());
        }

        return DivisionResource::collection($query
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
