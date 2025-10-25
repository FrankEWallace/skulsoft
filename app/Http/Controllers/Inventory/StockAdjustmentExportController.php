<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockAdjustmentListService;
use Illuminate\Http\Request;

class StockAdjustmentExportController extends Controller
{
    public function __invoke(Request $request, StockAdjustmentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
