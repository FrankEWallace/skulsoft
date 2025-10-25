<?php

namespace App\Services\Library;

use App\Concerns\ItemImport;
use App\Imports\Library\BookCopyImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BookCopyImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('book-copy');

        $this->validateFile($request);

        Excel::import(new BookCopyImport, $request->file('file'));

        $this->reportError('book-copy');
    }
}
