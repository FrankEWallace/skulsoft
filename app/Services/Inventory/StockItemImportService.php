<?php

namespace App\Services\Inventory;

use App\Concerns\ItemImport;
use App\Imports\Inventory\StockItemImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StockItemImportService
{
    use ItemImport;

    public function import(Request $request)
    {
        $this->deleteLogFile('stock_item');

        $this->validateFile($request);

        Excel::import(new StockItemImport, $request->file('file'));

        $this->reportError('stock_item');
    }
}
