<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\PaymentMethodRequest;
use App\Http\Resources\Finance\PaymentMethodResource;
use App\Models\Finance\PaymentMethod;
use App\Services\Finance\PaymentMethodListService;
use App\Services\Finance\PaymentMethodService;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, PaymentMethodService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, PaymentMethodListService $service)
    {
        return $service->paginate($request);
    }

    public function store(PaymentMethodRequest $request, PaymentMethodService $service)
    {
        $paymentMethod = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('finance.payment_method.payment_method')]),
            'payment_method' => PaymentMethodResource::make($paymentMethod),
        ]);
    }

    public function show(PaymentMethod $paymentMethod, PaymentMethodService $service)
    {
        return PaymentMethodResource::make($paymentMethod);
    }

    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod, PaymentMethodService $service)
    {
        $service->update($request, $paymentMethod);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('finance.payment_method.payment_method')]),
        ]);
    }

    public function destroy(PaymentMethod $paymentMethod, PaymentMethodService $service)
    {
        $service->deletable($paymentMethod);

        $paymentMethod->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('finance.payment_method.payment_method')]),
        ]);
    }
}
