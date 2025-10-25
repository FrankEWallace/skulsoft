<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Services\Transport\Vehicle\FuelRecordListService;
use Illuminate\Http\Request;

class FuelRecordExportController extends Controller
{
    public function __invoke(Request $request, FuelRecordListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
