<?php

namespace App\Http\Controllers\Approval\Report;

use App\Http\Controllers\Controller;
use App\Services\Approval\Report\RequestSummaryListService;
use Illuminate\Http\Request;

class RequestSummaryExportController extends Controller
{
    public function __invoke(Request $request, RequestSummaryListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
