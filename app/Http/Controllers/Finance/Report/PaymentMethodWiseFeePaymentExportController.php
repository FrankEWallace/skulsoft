<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\PaymentMethodWiseFeePaymentListService;
use Illuminate\Http\Request;

class PaymentMethodWiseFeePaymentExportController extends Controller
{
    public function __invoke(Request $request, PaymentMethodWiseFeePaymentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
