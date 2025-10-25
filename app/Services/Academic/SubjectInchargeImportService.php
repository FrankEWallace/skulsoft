<?php

namespace App\Services\Academic;

use App\Concerns\ItemImport;
use App\Imports\Academic\SubjectInchargeImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SubjectInchargeImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('subject_incharge');

        $this->validateFile($request);

        Excel::import(new SubjectInchargeImport, $request->file('file'));

        $this->reportError('subject_incharge');
    }
}
