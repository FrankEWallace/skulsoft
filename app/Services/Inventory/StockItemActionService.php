<?php

namespace App\Services\Inventory;

use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockItem;
use App\Models\Inventory\StockItemRecord;
use Illuminate\Http\Request;

class StockItemActionService
{
    public function recalculateQuantity(Request $request, StockItem $stockItem)
    {
        $balances = StockBalance::query()
            ->select('id', 'place_type', 'place_id', 'opening_quantity')
            ->where('stock_item_id', $stockItem->id)
            ->get()
            ->mapWithKeys(function ($balance) {
                $key = $balance->place_type.':'.$balance->place_id;

                return [$key => $balance];
            });

        $sumFrom = function ($modelType, $tableName) use ($stockItem) {
            return StockItemRecord::query()
                ->join($tableName, function ($join) use ($modelType, $tableName) {
                    $join->on('stock_item_records.model_id', '=', "$tableName.id")
                        ->where('stock_item_records.model_type', $modelType);
                })
                ->where('stock_item_records.stock_item_id', $stockItem->id)
                ->where('model_type', '!=', 'StockTransfer')
                ->select(
                    "$tableName.place_type",
                    "$tableName.place_id",
                    \DB::raw('SUM(stock_item_records.quantity) as total')
                )
                ->groupBy("$tableName.place_type", "$tableName.place_id")
                ->get()
                ->mapWithKeys(function ($row) {
                    $key = $row->place_type.':'.$row->place_id;

                    return [$key => $row->total];
                });
        };

        $transferFrom = StockItemRecord::query()
            ->join('stock_transfers', function ($join) {
                $join->on('stock_item_records.model_id', '=', 'stock_transfers.id')
                    ->where('stock_item_records.model_type', 'StockTransfer');
            })
            ->where('stock_item_records.stock_item_id', $stockItem->id)
            ->select(
                'stock_transfers.from_type as place_type',
                'stock_transfers.from_id as place_id',
                \DB::raw('SUM(stock_item_records.quantity) * -1 as total')
            )
            ->groupBy('stock_transfers.from_type', 'stock_transfers.from_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->place_type.':'.$row->place_id => $row->total]);

        $transferTo = StockItemRecord::query()
            ->join('stock_transfers', function ($join) {
                $join->on('stock_item_records.model_id', '=', 'stock_transfers.id')
                    ->where('stock_item_records.model_type', 'StockTransfer');
            })
            ->where('stock_item_records.stock_item_id', $stockItem->id)
            ->select(
                'stock_transfers.to_type as place_type',
                'stock_transfers.to_id as place_id',
                \DB::raw('SUM(stock_item_records.quantity) as total')
            )
            ->groupBy('stock_transfers.to_type', 'stock_transfers.to_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->place_type.':'.$row->place_id => $row->total]);

        $transfers = $transferFrom->mergeRecursive($transferTo)->map(function ($values) {
            return is_array($values) ? array_sum($values) : $values;
        });

        $purchases = $sumFrom('StockPurchase', 'stock_purchases');
        $returns = $sumFrom('StockReturn', 'stock_returns');
        $adjustments = $sumFrom('StockAdjustment', 'stock_adjustments');

        foreach ($balances as $key => $balance) {
            $newQuantity =
                ($purchases[$key] ?? 0)
                - ($returns[$key] ?? 0)
                + ($transfers[$key] ?? 0)
                + ($adjustments[$key] ?? 0);

            \DB::table('stock_balances')
                ->where('id', $balance->id)
                ->update(['current_quantity' => $newQuantity]);
        }
    }
}
