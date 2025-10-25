<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Services\DeviceListService;
use App\Services\DeviceService;
use Illuminate\Http\Request;

class DeviceController
{
    public function index(Request $request, DeviceListService $service)
    {
        return $service->paginate($request);
    }

    public function store(DeviceRequest $request, DeviceService $service)
    {
        $device = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('device.device')]),
            'device' => DeviceResource::make($device),
        ]);
    }

    public function show(Device $device, DeviceService $service): DeviceResource
    {
        return DeviceResource::make($device);
    }

    public function update(DeviceRequest $request, Device $device, DeviceService $service)
    {
        $service->update($request, $device);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('device.device')]),
        ]);
    }

    public function destroy(Device $device, DeviceService $service)
    {
        $service->deletable($device);

        $device->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('device.device')]),
        ]);
    }
}
