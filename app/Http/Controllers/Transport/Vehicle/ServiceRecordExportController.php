<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Services\Transport\Vehicle\ServiceRecordListService;
use Illuminate\Http\Request;

class ServiceRecordExportController extends Controller
{
    public function __invoke(Request $request, ServiceRecordListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
