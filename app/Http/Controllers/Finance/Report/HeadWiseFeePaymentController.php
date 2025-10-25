<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\HeadWiseFeePaymentListService;
use App\Services\Finance\Report\HeadWiseFeePaymentService;
use Illuminate\Http\Request;

class HeadWiseFeePaymentController extends Controller
{
    public function preRequisite(Request $request, HeadWiseFeePaymentService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, HeadWiseFeePaymentListService $service)
    {
        return $service->paginate($request);
    }
}
