<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockItemListService;
use Illuminate\Http\Request;

class StockItemExportController extends Controller
{
    public function __invoke(Request $request, StockItemListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
