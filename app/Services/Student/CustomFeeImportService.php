<?php

namespace App\Services\Student;

use App\Concerns\ItemImport;
use App\Imports\Student\CustomFeeImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CustomFeeImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('student_custom_fee');

        $this->validateFile($request);

        Excel::import(new CustomFeeImport, $request->file('file'));

        $this->reportError('student_custom_fee');
    }
}
