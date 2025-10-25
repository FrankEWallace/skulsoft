<?php

namespace App\Services\Employee;

use App\Concerns\ItemImport;
use App\Imports\Employee\EmployeeImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('employee');

        $this->validateFile($request);

        Excel::import(new EmployeeImport, $request->file('file'));

        $this->reportError('employee');
    }
}
