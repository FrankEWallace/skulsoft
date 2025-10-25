<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SetFeeRequest;
use App\Http\Requests\Student\UpdateFeeInstallmentRequest;
use App\Http\Requests\Student\UpdateFeeRequest;
use App\Models\Student\Student;
use App\Services\Student\FeeListService;
use App\Services\Student\FeeService;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function preRequisite(Request $request, string $student, FeeService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('setFee', $student);

        return response()->ok($service->preRequisite($request, $student));
    }

    public function fetchFee(Request $request, string $student, FeeListService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->fetchFee($request, $student));
    }

    public function listFee(Request $request, string $student, FeeListService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->listFee($request, $student));
    }

    public function getSiblingFees(Request $request, string $student, FeeListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->getSiblingFees($request, $student));
    }

    public function getFeeSummary(Request $request, string $student, FeeListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        return $student->getFeeSummary();
    }

    public function setFee(SetFeeRequest $request, string $student, FeeService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('setFee', $student);

        $service->setFee($request, $student);

        return response()->success([
            'message' => trans('global.set_result', ['attribute' => trans('student.fee.fee')]),
        ]);
    }

    public function updateFee(UpdateFeeRequest $request, Student $student, FeeService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student->uuid);

        $this->authorize('updateFee', $student);

        $service->updateFee($request, $student);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.fee.fee')]),
        ]);
    }

    public function updateFeeInstallment(UpdateFeeInstallmentRequest $request, string $student, $uuid, FeeService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('updateFee', $student);

        $service->updateFeeInstallment($request, $student, $uuid);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.fee.fee')]),
        ]);
    }

    public function exportFee(Request $request, string $student, FeeService $service)
    {
        abort(398, trans('general.errors.feature_under_development'));

        // $student = Student::findSummaryByUuidOrFail($student);

        // $this->authorize('view', $student);

        // $fee = $service->getFeeInstallment($request, $student, $uuid);

        // $fee->load('installment', 'concession', 'transportCircle', 'records.head');

        // return view('print.student.fee-installment', compact('student', 'fee'));
    }

    public function exportFeeGroup(Request $request, string $student, $uuid, FeeService $service)
    {
        abort(398, trans('general.errors.feature_under_development'));

        // $student = Student::findSummaryByUuidOrFail($student);

        // $this->authorize('view', $student);

        // $fee = $service->getFeeInstallment($request, $student, $uuid);

        // $fee->load('installment', 'concession', 'transportCircle', 'records.head');

        // return view('print.student.fee-installment', compact('student', 'fee'));
    }

    public function exportFeeInstallment(Request $request, string $student, $uuid, FeeService $service)
    {
        abort(398, trans('general.errors.feature_under_development'));

        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('view', $student);

        $fee = $service->getFeeInstallment($request, $student, $uuid);

        $fee->load('installment', 'concession', 'transportCircle', 'records.head');

        return view('print.student.fee-installment', compact('student', 'fee'));
    }

    public function resetFee(string $student, FeeService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('resetFee', $student);

        $service->resetFee($student);

        return response()->success([
            'message' => trans('global.reset_result', ['attribute' => trans('student.fee.fee')]),
        ]);
    }

    public function getStudentFees(string $student, FeeService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->getStudentFees($student);
    }

    public function setCustomConcession(Request $request, string $student, FeeService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('setCustomConcession', $student);

        $service->setCustomConcession($request, $student);
    }
}
