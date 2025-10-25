<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\BankTransferActionRequest;
use App\Http\Requests\Student\BankTransferRequest;
use App\Http\Requests\Student\PaymentRequest;
use App\Models\Student\Student;
use App\Services\Student\BankTransferService;
use App\Services\Student\PaymentService;
use App\Support\GeneratePDF;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use GeneratePDF;

    public function preRequisite(Request $request, string $student, PaymentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('makePayment', $student);

        return response()->ok($service->preRequisite($request, $student));
    }

    public function storeTempPayment(Request $request, string $student, PaymentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('makePayment', $student);

        return response()->ok($service->storeTempPayment($request, $student));
    }

    public function bankTransfer(BankTransferRequest $request, string $student, BankTransferService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('bankTransfer', $student);

        $bankTransfer = $service->bankTransfer($request, $student);

        return response()->success([
            'message' => trans('student.fee.bank_transfer_uploaded', [
                'code_number' => $bankTransfer->code_number,
                'amount' => $bankTransfer->amount->formatted,
            ]),
        ]);
    }

    public function bankTransferAction(BankTransferActionRequest $request, string $student, string $uuid, BankTransferService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('bankTransferAction', $student);

        $bankTransfer = $service->bankTransferAction($request, $student, $uuid);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.fee.bank_transfer')]),
        ]);
    }

    public function makePayment(PaymentRequest $request, string $student, PaymentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('makePayment', $student);

        $service->makePayment($request, $student);

        return response()->success([
            'message' => trans('global.paid', ['attribute' => trans('student.fee.fee')]),
        ]);
    }

    public function getPayment(Request $request, string $student, string $uuid, PaymentService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('view', $student);

        $transaction = $service->getPayment($request, $student, $uuid);

        return [];
    }

    public function export(Request $request, string $student, string $uuid, PaymentService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('exportPayment', $student);

        $transaction = $service->getPayment($request, $student, $uuid);

        $transaction->load('records.model.payments.head', 'payments.method');

        $student->load('batch.course.division.program');

        $rows = $service->getPaymentRows($transaction);

        $content = view()->first([config('config.print.custom_path').'student.fee-receipt', 'print.student.fee-receipt'], compact('student', 'transaction', 'rows'))->render();

        if ($request->query('output') == 'pdf') {
            $this->generatePDF($content);

            return;
        }

        return $content;
    }

    public function guestExport(Request $request, PaymentService $service)
    {
        $student = Student::query()
            ->summaryForGuest()
            ->where('code_number', '=', $request->query('code_number'))
            ->where('students.uuid', '=', $request->query('uuid'))
            ->firstOrFail();

        $transaction = $service->getPaymentByReferenceNumber($request, $student);

        $transaction->load('records.model.payments.head', 'payments.method');

        $student->load('batch.course.division.program');

        $rows = $service->getPaymentRows($transaction);

        $content = view()->first([config('config.print.custom_path').'student.fee-receipt', 'print.student.fee-receipt'], compact('student', 'transaction', 'rows'))->render();

        if ($request->query('output') == 'pdf') {
            $this->generatePDF($content);

            return;
        }

        return $content;
    }

    public function updatePayment(Request $request, string $student, string $uuid, PaymentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('updatePayment', $student);

        $service->updatePayment($request, $student, $uuid);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.fee.fee')]),
        ]);
    }

    public function cancelPayment(Request $request, string $student, string $uuid, PaymentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('cancelPayment', $student);

        $service->cancelPayment($request, $student, $uuid);

        return response()->success([
            'message' => trans('global.cancelled', ['attribute' => trans('student.fee.fee')]),
        ]);
    }
}
