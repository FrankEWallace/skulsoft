<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockCategoryListService;
use Illuminate\Http\Request;

class StockCategoryExportController extends Controller
{
    public function __invoke(Request $request, StockCategoryListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
