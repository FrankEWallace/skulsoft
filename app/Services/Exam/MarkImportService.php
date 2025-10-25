<?php

namespace App\Services\Exam;

use App\Concerns\ItemImport;
use App\Imports\Exam\MarkImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MarkImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('exam-mark');

        $this->validateFile($request);

        Excel::import(new MarkImport([]), $request->file('file'));

        $this->reportError('exam-mark');
    }
}
