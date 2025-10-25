<?php

namespace App\Http\Controllers\Student;

use App\Actions\Student\GetStudentFees;
use App\Contracts\Finance\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Models\Team;
use App\Services\Student\FeeListService;
use App\Services\Student\GuestPaymentService;
use App\Services\Student\OnlinePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class GuestPaymentController extends Controller
{
    public function preRequisite(GuestPaymentService $service)
    {
        return $service->preRequisite();
    }

    public function getPeriods(Team $team, GuestPaymentService $service)
    {
        return $service->getPeriods($team);
    }

    public function getCourses(Team $team, string $uuid, GuestPaymentService $service)
    {
        return $service->getCourses($team, $uuid);
    }

    public function getFeeDetail(Request $request, GuestPaymentService $service, FeeListService $feeListService)
    {
        $student = $service->getStudent($request);

        $data = (new GetStudentFees)->execute($student);

        $feeRecords = Arr::get($data, 'feeRecords');
        $previousDues = Arr::get($data, 'previousDues');

        $feeDetails = $feeListService->groupWiseFee($request, $student);

        return compact('feeRecords', 'previousDues', 'feeDetails');
    }

    public function initiate(Request $request, string $student, OnlinePaymentService $service, PaymentGateway $paymentGateway)
    {
        $student = Student::findSummaryByUuidForGuestOrFail($student);

        (new GetStudentFees)->validatePreviousDue($student);

        $service->setFinanceConfig($student->team_id);

        return response()->success($service->initiate($request, $student, $paymentGateway));
    }

    public function complete(Request $request, string $student, OnlinePaymentService $service, PaymentGateway $paymentGateway)
    {
        $student = Student::findSummaryByUuidForGuestOrFail($student);

        $service->setFinanceConfig($student->team_id);

        $transaction = $service->makePayment($request, $student, $paymentGateway);

        $referenceNumber = Arr::get($transaction->payment_gateway, 'reference_number');
        $amount = $transaction->amount->formatted;

        return response()->success([
            'message' => trans('student.fee.paid_online', ['reference' => $referenceNumber, 'amount' => $amount]),
        ]);
    }

    public function fail(Request $request, string $student, OnlinePaymentService $service, PaymentGateway $paymentGateway)
    {
        $student = Student::findSummaryByUuidForGuestOrFail($student);

        $service->setFinanceConfig($student->team_id);

        $service->failPayment($request, $student, $paymentGateway);

        return response()->success([
            'message' => trans('global.failed', ['attribute' => trans('student.payment.payment')]),
        ]);
    }
}
