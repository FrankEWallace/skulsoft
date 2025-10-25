<?php

namespace App\Services\Transport\Vehicle;

use App\Contracts\ListGenerator;
use App\Http\Resources\Transport\Vehicle\ServiceRecordResource;
use App\Models\Transport\Vehicle\ServiceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceRecordListService extends ListGenerator
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
                'label' => trans('transport.vehicle.service_record.props.date'),
                'print_label' => 'date.formatted',
                'print_sub_label' => 'next_due_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'log',
                'label' => trans('transport.vehicle.service_record.props.log'),
                'print_label' => 'log',
                'print_sub_label' => 'next_due_log',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'amount',
                'label' => trans('transport.vehicle.service_record.props.amount'),
                'print_label' => 'amount.formatted',
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
        return ServiceRecord::query()
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
        return ServiceRecordResource::collection($this->filter($request)
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
