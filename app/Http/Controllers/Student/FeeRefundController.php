<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\FeeRefundRequest;
use App\Http\Resources\Student\FeeRefundResource;
use App\Models\Student\Student;
use App\Services\Student\FeeRefundListService;
use App\Services\Student\FeeRefundService;
use Illuminate\Http\Request;

class FeeRefundController extends Controller
{
    public function preRequisite(Request $request, string $student, FeeRefundService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->preRequisite($request, $student));
    }

    public function index(Request $request, string $student, FeeRefundListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    public function store(FeeRefundRequest $request, string $student, FeeRefundService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('updateFee', $student);

        $feeRefund = $service->create($request, $student);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.fee_refund.fee_refund')]),
            'fee_refund' => FeeRefundResource::make($feeRefund),
        ]);
    }

    public function show(string $student, string $feeRefund, FeeRefundService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $feeRefund = $service->findByUuidOrFail($student, $feeRefund);

        $feeRefund->load('records.head', 'transaction.payments.ledger', 'transaction.payments.method');

        return FeeRefundResource::make($feeRefund);
    }

    public function update(FeeRefundRequest $request, string $student, string $feeRefund, FeeRefundService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('updateFee', $student);

        $feeRefund = $service->findByUuidOrFail($student, $feeRefund);

        $service->update($request, $student, $feeRefund);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.fee_refund.fee_refund')]),
        ]);
    }

    public function destroy(string $student, string $feeRefund, FeeRefundService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('updateFee', $student);

        $feeRefund = $service->findByUuidOrFail($student, $feeRefund);

        $service->deletable($student, $feeRefund);

        $feeRefund->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.fee_refund.fee_refund')]),
        ]);
    }
}
