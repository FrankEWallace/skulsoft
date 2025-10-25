<?php

namespace App\Services\Academic;

use App\Concerns\ItemImport;
use App\Imports\Academic\BatchImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BatchImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('batch');

        $this->validateFile($request);

        Excel::import(new BatchImport, $request->file('file'));

        $this->reportError('batch');
    }
}
