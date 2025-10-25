<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\OnlineFeePaymentListService;
use Illuminate\Http\Request;

class OnlineFeePaymentExportController extends Controller
{
    public function __invoke(Request $request, OnlineFeePaymentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
