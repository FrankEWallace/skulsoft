<?php

namespace App\Services\Transport\Vehicle;

use App\Contracts\ListGenerator;
use App\Http\Resources\Transport\Vehicle\DocumentResource;
use App\Models\Document;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentListService extends ListGenerator
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
                'key' => 'title',
                'label' => trans('transport.vehicle.document.props.title'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('transport.vehicle.document.props.type'),
                'print_label' => 'type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'startDate',
                'label' => trans('transport.vehicle.document.props.start_date'),
                'print_label' => 'startDate.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'endDate',
                'label' => trans('transport.vehicle.document.props.end_date'),
                'print_label' => 'endDate.formatted',
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
        return Document::query()
            ->whereHasMorph(
                'documentable',
                [Vehicle::class],
                function (Builder $query) {
                    $query->byTeam();
                }
            )
            ->with('documentable', 'type')
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return DocumentResource::collection($this->filter($request)
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
