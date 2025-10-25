<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeSummaryListService;
use Illuminate\Http\Request;

class FeeSummaryExportController extends Controller
{
    public function __invoke(Request $request, FeeSummaryListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
