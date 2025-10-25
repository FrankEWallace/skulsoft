<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockTransferListService;
use Illuminate\Http\Request;

class StockTransferExportController extends Controller
{
    public function __invoke(Request $request, StockTransferListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
