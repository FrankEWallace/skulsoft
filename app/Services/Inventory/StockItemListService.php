<?php

namespace App\Services\Inventory;

use App\Contracts\ListGenerator;
use App\Http\Resources\Inventory\StockItemResource;
use App\Models\Asset\Building\Room;
use App\Models\Inventory\StockItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class StockItemListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('inventory.stock_item.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'code',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'category',
                'label' => trans('inventory.stock_category.stock_category'),
                'print_label' => 'category.name',
                'print_sub_label' => 'category.inventory.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'quantity',
                'label' => trans('inventory.stock_item.props.quantity'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'unit',
                'label' => trans('inventory.stock_item.props.unit'),
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
        $inventory = $request->query('inventory');
        $place = $request->query('place');
        $stockCategories = Str::toArray($request->query('stock_categories'));

        return StockItem::query()
            ->filterAccessible()
            ->select('stock_items.*', \DB::raw('SUM(stock_balances.opening_quantity + stock_balances.current_quantity) as total_quantity'))
            ->leftJoin('stock_balances', 'stock_items.id', '=', 'stock_balances.stock_item_id')
            ->with('category.inventory')
            ->when($inventory, function ($q, $inventory) {
                $q->whereHas('category', function ($q) use ($inventory) {
                    $q->whereHas('inventory', function ($q) use ($inventory) {
                        $q->where('uuid', $inventory);
                    });
                });
            })->when($place, function ($q, $place) {
                $roomId = Room::query()
                    ->byTeam()
                    ->whereUuid($place)
                    ->first();

                $q->where('stock_balances.place_id', $roomId?->id);
            })
            ->when($stockCategories, function ($q, $stockCategories) {
                $q->whereHas('category', function ($q) use ($stockCategories) {
                    $q->whereIn('uuid', $stockCategories);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:code',
                'App\QueryFilters\UuidMatch',
            ])
            ->groupBy('stock_items.id');
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return StockItemResource::collection($this->filter($request)
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
