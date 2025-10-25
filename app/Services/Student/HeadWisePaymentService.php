<?php

namespace App\Services\Student;

use App\Actions\Student\GetStudentFees;
use App\Actions\Student\HeadWisePayment;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class HeadWisePaymentService
{
    public function makePayment(Request $request, Student $student): void
    {
        (new GetStudentFees)->validatePreviousDue($student);

        $params = $request->all();

        $response = (new HeadWisePayment)->execute($student, $params);

        if (Arr::get($response, 'status')) {
            return;
        }

        throw ValidationException::withMessages([Arr::get($response, 'key') => Arr::get($response, 'message')]);
    }
}
