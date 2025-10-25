<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockReturnListService;
use Illuminate\Http\Request;

class StockReturnExportController extends Controller
{
    public function __invoke(Request $request, StockReturnListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
