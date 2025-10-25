<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockPurchaseListService;
use Illuminate\Http\Request;

class StockPurchaseExportController extends Controller
{
    public function __invoke(Request $request, StockPurchaseListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
