<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\HeadWiseFeePaymentListService;
use Illuminate\Http\Request;

class HeadWiseFeePaymentExportController extends Controller
{
    public function __invoke(Request $request, HeadWiseFeePaymentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
