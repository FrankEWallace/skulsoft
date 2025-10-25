<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeePaymentListService;
use Illuminate\Http\Request;

class FeePaymentExportController extends Controller
{
    public function __invoke(Request $request, FeePaymentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
