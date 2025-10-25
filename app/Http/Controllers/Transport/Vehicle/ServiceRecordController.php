<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\Vehicle\ServiceRecordRequest;
use App\Http\Resources\Transport\Vehicle\ServiceRecordResource;
use App\Models\Transport\Vehicle\ServiceRecord;
use App\Services\Transport\Vehicle\ServiceRecordListService;
use App\Services\Transport\Vehicle\ServiceRecordService;
use Illuminate\Http\Request;

class ServiceRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, ServiceRecordService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, ServiceRecordListService $service)
    {
        $this->authorize('viewAny', ServiceRecord::class);

        return $service->paginate($request);
    }

    public function store(ServiceRecordRequest $request, ServiceRecordService $service)
    {
        $this->authorize('create', ServiceRecord::class);

        $vehicleServiceRecord = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('transport.vehicle.service_record.service_record')]),
            'vehicle' => ServiceRecordResource::make($vehicleServiceRecord),
        ]);
    }

    public function show(string $vehicleServiceRecord, ServiceRecordService $service)
    {
        $vehicleServiceRecord = ServiceRecord::findByUuidOrFail($vehicleServiceRecord);

        $this->authorize('view', $vehicleServiceRecord);

        $vehicleServiceRecord->load('vehicle', 'media');

        return ServiceRecordResource::make($vehicleServiceRecord);
    }

    public function update(ServiceRecordRequest $request, string $vehicleServiceRecord, ServiceRecordService $service)
    {
        $vehicleServiceRecord = ServiceRecord::findByUuidOrFail($vehicleServiceRecord);

        $this->authorize('update', $vehicleServiceRecord);

        $service->update($request, $vehicleServiceRecord);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('transport.vehicle.service_record.service_record')]),
        ]);
    }

    public function destroy(string $vehicleServiceRecord, ServiceRecordService $service)
    {
        $vehicleServiceRecord = ServiceRecord::findByUuidOrFail($vehicleServiceRecord);

        $this->authorize('delete', $vehicleServiceRecord);

        $service->deletable($vehicleServiceRecord);

        $vehicleServiceRecord->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('transport.vehicle.service_record.service_record')]),
        ]);
    }

    public function downloadMedia(string $vehicleServiceRecord, string $uuid, ServiceRecordService $service)
    {
        $vehicleServiceRecord = ServiceRecord::findByUuidOrFail($vehicleServiceRecord);

        $this->authorize('view', $vehicleServiceRecord);

        return $vehicleServiceRecord->downloadMedia($uuid);
    }
}
