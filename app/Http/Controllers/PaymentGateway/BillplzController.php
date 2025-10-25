<?php

namespace App\Http\Controllers\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Services\PaymentGateway\BillplzService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BillplzController extends Controller
{
    public function getResponse(Request $request, BillplzService $service)
    {
        $response = $service->getResponse($request);

        if ($response) {
            return response()->success([]);
        }

        throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
    }

    public function redirectUrl(Request $request, BillplzService $service)
    {
        return $service->redirectUrl($request);
    }
}
