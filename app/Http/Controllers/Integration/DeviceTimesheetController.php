<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\DeviceTimesheetRequest;
use App\Services\Integration\DeviceTimesheetService;
use Illuminate\Http\Request;

class DeviceTimesheetController extends Controller
{
    public function store(DeviceTimesheetRequest $request, DeviceTimesheetService $service)
    {
        $response = $service->store($request);

        return response()->success($response);
    }

    public function import(Request $request, DeviceTimesheetService $service)
    {
        $response = $service->import($request);

        return response()->success($response);
    }
}
