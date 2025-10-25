<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockItem;
use App\Services\Inventory\StockItemActionService;
use Illuminate\Http\Request;

class StockItemActionController extends Controller
{
    public function recalculateQuantity(Request $request, string $stockItem, StockItemActionService $service)
    {
        $stockItem = StockItem::findByUuidOrFail($stockItem);

        $this->authorize('update', $stockItem);

        $service->recalculateQuantity($request, $stockItem);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.stock_item.stock_item')]),
        ]);
    }
}
