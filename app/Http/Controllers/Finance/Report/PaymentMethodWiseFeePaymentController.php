<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\PaymentMethodWiseFeePaymentListService;
use App\Services\Finance\Report\PaymentMethodWiseFeePaymentService;
use Illuminate\Http\Request;

class PaymentMethodWiseFeePaymentController extends Controller
{
    public function preRequisite(Request $request, PaymentMethodWiseFeePaymentService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, PaymentMethodWiseFeePaymentListService $service)
    {
        return $service->paginate($request);
    }
}
