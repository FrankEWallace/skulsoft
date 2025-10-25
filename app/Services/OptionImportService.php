<?php

namespace App\Services;

use App\Concerns\ItemImport;
use App\Imports\OptionImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OptionImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('option');

        $this->validateFile($request);

        Excel::import(new OptionImport, $request->file('file'));

        $this->reportError('option');
    }
}
