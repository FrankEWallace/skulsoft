<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\OnlineFeePaymentListService;
use App\Services\Finance\Report\OnlineFeePaymentService;
use Illuminate\Http\Request;

class OnlineFeePaymentController extends Controller
{
    public function preRequisite(Request $request, OnlineFeePaymentService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, OnlineFeePaymentListService $service)
    {
        return $service->paginate($request);
    }
}
