<?php

namespace App\Services\Reception;

use App\Concerns\ItemImport;
use App\Imports\Reception\EnquiryImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EnquiryImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('enquiry');

        $this->validateFile($request);

        Excel::import(new EnquiryImport, $request->file('file'));

        $this->reportError('enquiry');
    }
}
