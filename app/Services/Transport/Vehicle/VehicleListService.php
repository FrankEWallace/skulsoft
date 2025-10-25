<?php

namespace App\Services\Transport\Vehicle;

use App\Contracts\ListGenerator;
use App\Http\Resources\Transport\Vehicle\VehicleResource;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('transport.vehicle.props.name'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'registrationNumber',
                'label' => trans('transport.vehicle.props.registration_number'),
                'print_label' => 'registration_number',
                'print_sub_label' => 'registration_place',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'registrationDate',
                'label' => trans('transport.vehicle.props.registration_date'),
                'print_label' => 'registration_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'modelNumber',
                'label' => trans('transport.vehicle.props.model_number'),
                'print_label' => 'model_number',
                'print_sub_label' => 'model',
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
        $registrationNumber = $request->query('registration_number');

        return Vehicle::query()
            ->byTeam()
            ->when($registrationNumber, function ($q, $registrationNumber) {
                $q->where('registration->number', 'like', "%{$registrationNumber}%");
            })
            ->filter([
                'App\QueryFilters\LikeMatch:model_number',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return VehicleResource::collection($this->filter($request)
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
