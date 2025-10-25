<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Services\Transport\Vehicle\TravelRecordListService;
use Illuminate\Http\Request;

class TravelRecordExportController extends Controller
{
    public function __invoke(Request $request, TravelRecordListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
