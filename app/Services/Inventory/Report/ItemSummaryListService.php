<?php

namespace App\Services\Inventory\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Inventory\Report\ItemSummaryListResource;
use App\Models\Inventory\StockItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ItemSummaryListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'sno',
                'label' => trans('general.sno'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('inventory.stock_item.props.name'),
                'print_label' => 'name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'category_name',
                'label' => trans('inventory.stock_category.stock_category'),
                'print_label' => 'category_name',
                'print_sub_label' => 'inventory_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'opening_balance',
                'label' => trans('inventory.stock_item.opening_balance'),
                'print_label' => 'opening_balance',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'purchased_quantity',
                'label' => trans('inventory.stock_item.purchased_quantity'),
                'print_label' => 'purchased_quantity',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'returned_quantity',
                'label' => trans('inventory.stock_item.returned_quantity'),
                'print_label' => 'returned_quantity',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'adjusted_quantity',
                'label' => trans('inventory.stock_item.adjusted_quantity'),
                'print_label' => 'adjusted_quantity',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'current_balance',
                'label' => trans('inventory.stock_item.current_balance'),
                'print_label' => 'current_balance',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        // if (request()->ajax()) {
        //     $headers[] = $this->actionHeader;
        // }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $request->validate([
            'inventory' => 'required|uuid',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        return StockItem::query()
            ->select('stock_items.*', 'stock_categories.name as category_name', 'inventories.name as inventory_name',
            \DB::raw("
                COALESCE((
                    SELECT SUM(opening_quantity)
                    FROM stock_balances
                    WHERE stock_balances.stock_item_id = stock_items.id
                ), 0) as pre_opening_balance"
            ),
            \DB::raw("
                COALESCE((
                    SELECT SUM(CASE
                        WHEN model_type = 'StockPurchase' AND stock_purchases.date < '{$startDate}' THEN quantity
                        WHEN model_type = 'StockReturn' AND stock_returns.date < '{$startDate}' THEN -quantity
                        WHEN model_type = 'StockAdjustment' AND stock_adjustments.date < '{$startDate}' THEN quantity
                        ELSE 0
                    END)
                    FROM stock_item_records
                    LEFT JOIN stock_purchases ON stock_item_records.model_type = 'StockPurchase' AND stock_item_records.model_id = stock_purchases.id
                    LEFT JOIN stock_returns ON stock_item_records.model_type = 'StockReturn' AND stock_item_records.model_id = stock_returns.id
                    LEFT JOIN stock_adjustments ON stock_item_records.model_type = 'StockAdjustment' AND stock_item_records.model_id = stock_adjustments.id
                    WHERE stock_item_records.stock_item_id = stock_items.id
                ), 0)
             AS opening_balance"),
            \DB::raw("COALESCE((
                SELECT SUM(stock_item_records.quantity)
                FROM stock_item_records
                JOIN stock_purchases ON stock_item_records.model_type = 'StockPurchase' AND stock_item_records.model_id = stock_purchases.id
                WHERE stock_item_records.stock_item_id = stock_items.id AND stock_purchases.date BETWEEN '{$startDate}' AND '{$endDate}'
            ), 0) AS purchased_quantity"),
            \DB::raw("COALESCE((
                SELECT SUM(stock_item_records.quantity)
                FROM stock_item_records
                JOIN stock_returns ON stock_item_records.model_type = 'StockReturn' AND stock_item_records.model_id = stock_returns.id
                WHERE stock_item_records.stock_item_id = stock_items.id AND stock_returns.date BETWEEN '{$startDate}' AND '{$endDate}'
            ), 0) AS returned_quantity"),
            \DB::raw("COALESCE((
                SELECT SUM(stock_item_records.quantity)
                FROM stock_item_records
                JOIN stock_adjustments ON stock_item_records.model_type = 'StockAdjustment' AND stock_item_records.model_id = stock_adjustments.id
                WHERE stock_item_records.stock_item_id = stock_items.id AND stock_adjustments.date BETWEEN '{$startDate}' AND '{$endDate}'
            ), 0) AS adjusted_quantity"),
            \DB::raw("
                COALESCE((
                    SELECT SUM(CASE
                        WHEN model_type = 'StockPurchase' AND stock_purchases.date < '{$endDate}' THEN quantity
                        WHEN model_type = 'StockReturn' AND stock_returns.date < '{$endDate}' THEN -quantity
                        WHEN model_type = 'StockAdjustment' AND stock_adjustments.date < '{$endDate}' THEN quantity
                        ELSE 0
                    END)
                    FROM stock_item_records
                    LEFT JOIN stock_purchases ON stock_item_records.model_type = 'StockPurchase' AND stock_item_records.model_id = stock_purchases.id
                    LEFT JOIN stock_returns ON stock_item_records.model_type = 'StockReturn' AND stock_item_records.model_id = stock_returns.id
                    LEFT JOIN stock_adjustments ON stock_item_records.model_type = 'StockAdjustment' AND stock_item_records.model_id = stock_adjustments.id
                    WHERE stock_item_records.stock_item_id = stock_items.id
                ), 0)
             AS current_balance"),
            )
            ->join('stock_categories', 'stock_items.stock_category_id', '=', 'stock_categories.id')
            ->join('inventories', 'stock_categories.inventory_id', '=', 'inventories.id')
            ->where('inventories.uuid', $request->inventory)
            ->filter([
                'App\QueryFilters\LikeMatch:name',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $records = $this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page');

        return ItemSummaryListResource::collection($records)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Item Summary Report',
                    'sno' => $this->getSno(),
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                    'has_footer' => false,
                ],
                'footers' => [],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
