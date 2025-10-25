<?php

namespace App\Services\Transport;

use App\Contracts\ListGenerator;
use App\Http\Resources\Transport\RouteListResource;
use App\Models\Transport\Route;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RouteListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'maxCapacity'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('transport.route.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'direction.label',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'vehicle',
                'label' => trans('transport.vehicle.vehicle'),
                'print_label' => 'vehicle.registration_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'time',
                'label' => trans('general.time'),
                'print_label' => 'arrival_starts_at.formatted',
                'print_sub_label' => 'departure_starts_at.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'maxCapacity',
                'label' => trans('transport.route.props.max_capacity'),
                'print_label' => 'max_capacity',
                'print_sub_label' => 'route_passengers_count',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'routeStoppagesCount',
                'label' => trans('transport.stoppage.stoppage'),
                'print_label' => 'route_stoppages_count',
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
        return Route::query()
            ->with('vehicle')
            ->withCount('routeStoppages')
            ->withCount('routePassengers')
            ->byPeriod()
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return RouteListResource::collection($this->filter($request)
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
