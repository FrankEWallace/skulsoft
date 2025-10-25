<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\DetailedFeePaymentListService;
use Illuminate\Http\Request;

class DetailedFeePaymentExportController extends Controller
{
    public function __invoke(Request $request, DetailedFeePaymentListService $service)
    {
        return $service->list($request);
    }
}
