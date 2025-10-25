<?php

namespace App\Services\Inventory;

use App\Concerns\ItemImport;
use App\Imports\Inventory\StockCategoryImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StockCategoryImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('stock_category');

        $this->validateFile($request);

        Excel::import(new StockCategoryImport, $request->file('file'));

        $this->reportError('stock_category');
    }
}
