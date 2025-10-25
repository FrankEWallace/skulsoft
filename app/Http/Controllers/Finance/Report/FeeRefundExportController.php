<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeRefundListService;
use Illuminate\Http\Request;

class FeeRefundExportController extends Controller
{
    public function __invoke(Request $request, FeeRefundListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
