<?php

namespace App\Http\Controllers\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Services\PaymentGateway\CcavenueService;
use Illuminate\Http\Request;

class CcavenueController extends Controller
{
    public function checkStatus(Request $request, CcavenueService $service)
    {
        return $service->checkStatus($request);
    }

    public function getResponse(Request $request, CcavenueService $service)
    {
        return $service->getResponse($request);
    }

    public function cancel(Request $request, CcavenueService $service)
    {
        return $service->cancel($request);
    }
}
