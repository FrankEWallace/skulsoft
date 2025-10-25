<?php

namespace App\Services\Transport\Vehicle;

use App\Contracts\ListGenerator;
use App\Http\Resources\Transport\Vehicle\FuelRecordResource;
use App\Models\Transport\Vehicle\FuelRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FuelRecordListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'vehicle',
                'label' => trans('transport.vehicle.vehicle'),
                'print_label' => 'vehicle.registration_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('transport.vehicle.fuel_record.props.date'),
                'print_label' => 'date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'quantity',
                'label' => trans('transport.vehicle.fuel_record.props.quantity'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'pricePerUnit',
                'label' => trans('transport.vehicle.fuel_record.props.price_per_unit'),
                'print_label' => 'price_per_unit.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'pricePerUnit',
                'label' => trans('transport.vehicle.fuel_record.props.cost'),
                'print_label' => 'cost.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'log',
                'label' => trans('transport.vehicle.fuel_record.props.log'),
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
        return FuelRecord::query()
            ->whereHas('vehicle', function ($q) {
                $q->byTeam();
            })
            ->with('vehicle')
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return FuelRecordResource::collection($this->filter($request)
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
