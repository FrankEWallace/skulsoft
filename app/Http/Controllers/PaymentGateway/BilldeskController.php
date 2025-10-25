<?php

namespace App\Http\Controllers\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Services\PaymentGateway\BilldeskService;
use Illuminate\Http\Request;

class BilldeskController extends Controller
{
    public function checkStatus(Request $request, BilldeskService $service)
    {
        return $service->checkStatus($request);
    }

    public function getResponse(Request $request, BilldeskService $service)
    {
        if (empty($request->all())) {
            return view('gateways.response.billdesk');
        }

        return $service->getResponse($request);
    }

    public function cancel(Request $request, BilldeskService $service)
    {
        return $service->cancel($request);
    }
}
