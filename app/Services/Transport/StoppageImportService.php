<?php

namespace App\Services\Transport;

use App\Concerns\ItemImport;
use App\Imports\Transport\StoppageImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StoppageImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('stoppage');

        $this->validateFile($request);

        Excel::import(new StoppageImport, $request->file('file'));

        $this->reportError('stoppage');
    }
}
