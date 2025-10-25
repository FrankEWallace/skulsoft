<?php

namespace App\Http\Controllers\Transport\Report;

use App\Http\Controllers\Controller;
use App\Services\Transport\Report\BatchWiseRouteListService;
use Illuminate\Http\Request;

class BatchWiseRouteExportController extends Controller
{
    public function __invoke(Request $request, BatchWiseRouteListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
