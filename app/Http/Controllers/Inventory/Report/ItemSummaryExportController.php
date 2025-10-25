<?php

namespace App\Http\Controllers\Inventory\Report;

use App\Http\Controllers\Controller;
use App\Services\Inventory\Report\ItemSummaryListService;
use Illuminate\Http\Request;

class ItemSummaryExportController extends Controller
{
    public function __invoke(Request $request, ItemSummaryListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
