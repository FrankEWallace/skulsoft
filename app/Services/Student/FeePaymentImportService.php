<?php

namespace App\Services\Student;

use App\Concerns\ItemImport;
use App\Imports\Student\FeePaymentImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class FeePaymentImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        // throw ValidationException::withMessages(['message' => trans('general.errors.feature_under_development')]);

        $this->deleteLogFile('student_fee_payment');

        $this->validateFile($request);

        Excel::import(new FeePaymentImport, $request->file('file'));

        $this->reportError('student_fee_payment');
    }
}
