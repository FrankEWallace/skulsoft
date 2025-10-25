<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Services\Transport\Vehicle\VehicleListService;
use Illuminate\Http\Request;

class VehicleExportController extends Controller
{
    public function __invoke(Request $request, VehicleListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
