<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\HeadWisePaymentRequest;
use App\Models\Student\Student;
use App\Services\Student\HeadWisePaymentService;

class HeadWisePaymentController extends Controller
{
    public function makePayment(HeadWisePaymentRequest $request, string $student, HeadWisePaymentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('makeHeadWisePayment', $student);

        $service->makePayment($request, $student);

        return response()->success([
            'message' => trans('global.paid', ['attribute' => trans('student.fee.fee')]),
        ]);
    }
}
