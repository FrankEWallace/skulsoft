<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockItemImportService;
use Illuminate\Http\Request;

class StockItemImportController extends Controller
{
    public function __invoke(Request $request, StockItemImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('inventory.stock_item.stock_item')]),
        ]);
    }
}
