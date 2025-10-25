<?php

namespace App\Services\Employee;

use App\Concerns\ItemImport;
use App\Imports\Employee\DesignationImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DesignationImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('designation');

        $this->validateFile($request);

        Excel::import(new DesignationImport, $request->file('file'));

        $this->reportError('designation');
    }
}
