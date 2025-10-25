<?php

namespace App\Services\Student;

use App\Concerns\ItemImport;
use App\Imports\Student\StudentImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('student');

        $this->validateFile($request);

        Excel::import(new StudentImport, $request->file('file'));

        $this->reportError('student');
    }
}
