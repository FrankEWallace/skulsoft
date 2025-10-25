<?php

namespace App\Services\Inventory;

use App\Contracts\ListGenerator;
use App\Http\Resources\Inventory\StockTransferResource;
use App\Models\Asset\Building\Room;
use App\Models\Inventory\StockTransfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class StockTransferListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('inventory.stock_transfer.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'inventory.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'fromPlace',
                'label' => trans('inventory.stock_transfer.props.from_place'),
                'print_label' => 'from.name',
                'print_sub_label' => 'from.floor.name_with_block',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'toPlace',
                'label' => trans('inventory.stock_transfer.props.to_place'),
                'print_label' => 'to.name',
                'print_sub_label' => 'to.floor.name_with_block',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('inventory.stock_transfer.props.date'),
                'print_label' => 'date.formatted',
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
        $inventory = $request->query('inventory');
        $fromPlaces = Str::toArray($request->query('from_places'));
        $toPlaces = Str::toArray($request->query('to_places'));

        return StockTransfer::query()
            ->filterAccessible()
            ->with(['inventory', 'from' => fn ($q) => $q->withFloorAndBlock(), 'to' => fn ($q) => $q->withFloorAndBlock()])
            ->when($inventory, function ($q, $inventory) {
                return $q->whereHas('inventory', function ($q) use ($inventory) {
                    $q->where('uuid', $inventory);
                });
            })
            ->when($fromPlaces, function ($q, $fromPlaces) {
                $q->whereHasMorph(
                    'from',
                    [Room::class],
                    function (Builder $query) use ($fromPlaces) {
                        $query->whereIn('uuid', $fromPlaces);
                    }
                );
            })
            ->when($toPlaces, function ($q, $toPlaces) {
                $q->whereHasMorph(
                    'to',
                    [Room::class],
                    function (Builder $query) use ($toPlaces) {
                        $query->whereIn('uuid', $toPlaces);
                    }
                );
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return StockTransferResource::collection($this->filter($request)
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
