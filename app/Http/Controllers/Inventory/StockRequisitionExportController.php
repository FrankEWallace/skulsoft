<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockRequisitionListService;
use Illuminate\Http\Request;

class StockRequisitionExportController extends Controller
{
    public function __invoke(Request $request, StockRequisitionListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
