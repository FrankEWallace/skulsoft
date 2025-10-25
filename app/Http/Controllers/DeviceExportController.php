<?php

namespace App\Http\Controllers;

use App\Services\DeviceListService;
use Illuminate\Http\Request;

class DeviceExportController extends Controller
{
    public function __invoke(Request $request, DeviceListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
