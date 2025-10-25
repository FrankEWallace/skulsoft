<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\PaymentMethodListService;
use Illuminate\Http\Request;

class PaymentMethodExportController extends Controller
{
    public function __invoke(Request $request, PaymentMethodListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
