<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeePaymentListService;
use App\Services\Finance\Report\FeePaymentService;
use Illuminate\Http\Request;

class FeePaymentController extends Controller
{
    public function preRequisite(Request $request, FeePaymentService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, FeePaymentListService $service)
    {
        return $service->paginate($request);
    }
}
