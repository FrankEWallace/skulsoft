<?php

namespace App\Http\Controllers\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Services\PaymentGateway\PayzoneService;
use Illuminate\Http\Request;

class PayzoneController extends Controller
{
    public function getResponse(Request $request, PayzoneService $service)
    {
        return $service->getResponse($request);
    }

    public function cancel(Request $request, PayzoneService $service)
    {
        return $service->cancel($request);
    }
}
