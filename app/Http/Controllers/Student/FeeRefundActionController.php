<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\FeeRefundActionService;
use Illuminate\Http\Request;

class FeeRefundActionController extends Controller
{
    public function cancel(Request $request, string $student, string $uuid, FeeRefundActionService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('updateFee', $student);

        $service->cancel($request, $student, $uuid);

        return response()->success([
            'message' => trans('global.cancelled', ['attribute' => trans('student.fee_refund.fee_refund')]),
        ]);
    }
}
