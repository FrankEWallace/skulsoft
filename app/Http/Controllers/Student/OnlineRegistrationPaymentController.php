<?php

namespace App\Http\Controllers\Student;

use App\Contracts\Finance\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Services\Student\OnlineRegistrationPaymentService;
use App\Services\Student\OnlineRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OnlineRegistrationPaymentController extends Controller
{
    public function preRequisite(Request $request, string $number, OnlineRegistrationService $service, OnlineRegistrationPaymentService $paymentService)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        return $paymentService->preRequisite($request, $registration);
    }

    public function initiate(Request $request, string $number, OnlineRegistrationService $service, OnlineRegistrationPaymentService $paymentService, PaymentGateway $paymentGateway)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $service->setFinanceConfig($registration->period->team_id);

        return response()->success($paymentService->initiate($request, $registration, $paymentGateway));
    }

    public function complete(Request $request, string $number, OnlineRegistrationService $service, OnlineRegistrationPaymentService $paymentService, PaymentGateway $paymentGateway)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $service->setFinanceConfig($registration->period->team_id);

        $transaction = $paymentService->makePayment($request, $registration, $paymentGateway);

        $referenceNumber = Arr::get($transaction->payment_gateway, 'reference_number');
        $amount = $transaction->amount->formatted;

        return response()->success([
            'message' => trans('student.fee.paid_online', [
                'reference' => $referenceNumber, 'amount' => $amount,
            ]),
        ]);
    }

    public function fail(Request $request, string $number, OnlineRegistrationService $service, OnlineRegistrationPaymentService $paymentService, PaymentGateway $paymentGateway)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $service->setFinanceConfig($registration->period->team_id);

        $paymentService->failPayment($request, $registration, $paymentGateway);

        return response()->success([
            'message' => trans('global.failed', ['attribute' => trans('student.payment.payment')]),
        ]);
    }
}
