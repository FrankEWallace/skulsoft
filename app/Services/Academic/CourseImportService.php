<?php

namespace App\Services\Academic;

use App\Concerns\ItemImport;
use App\Imports\Academic\CourseImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CourseImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('course');

        $this->validateFile($request);

        Excel::import(new CourseImport, $request->file('file'));

        $this->reportError('course');
    }
}
