<?php

namespace App\Services\Library;

use App\Concerns\ItemImport;
use App\Imports\Library\BookImport;
use App\Imports\Library\BookImportWithCopy;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BookImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('book');

        $this->validateFile($request);

        if ($request->boolean('with_copy') || $request->boolean('withCopy')) {
            Excel::import(new BookImportWithCopy, $request->file('file'));
        } else {
            Excel::import(new BookImport, $request->file('file'));
        }

        $this->reportError('book');
    }
}
