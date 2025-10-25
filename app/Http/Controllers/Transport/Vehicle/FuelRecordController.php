<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\Vehicle\FuelRecordRequest;
use App\Http\Resources\Transport\Vehicle\FuelRecordResource;
use App\Models\Transport\Vehicle\FuelRecord;
use App\Services\Transport\Vehicle\FuelRecordListService;
use App\Services\Transport\Vehicle\FuelRecordService;
use Illuminate\Http\Request;

class FuelRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, FuelRecordService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, FuelRecordListService $service)
    {
        $this->authorize('viewAny', FuelRecord::class);

        return $service->paginate($request);
    }

    public function store(FuelRecordRequest $request, FuelRecordService $service)
    {
        $this->authorize('create', FuelRecord::class);

        $vehicleFuelRecord = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('transport.vehicle.fuel_record.fuel_record')]),
            'vehicle' => FuelRecordResource::make($vehicleFuelRecord),
        ]);
    }

    public function show(string $vehicleFuelRecord, FuelRecordService $service)
    {
        $vehicleFuelRecord = FuelRecord::findByUuidOrFail($vehicleFuelRecord);

        $this->authorize('view', $vehicleFuelRecord);

        $vehicleFuelRecord->load('vehicle', 'media');

        return FuelRecordResource::make($vehicleFuelRecord);
    }

    public function update(FuelRecordRequest $request, string $vehicleFuelRecord, FuelRecordService $service)
    {
        $vehicleFuelRecord = FuelRecord::findByUuidOrFail($vehicleFuelRecord);

        $this->authorize('update', $vehicleFuelRecord);

        $service->update($request, $vehicleFuelRecord);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('transport.vehicle.fuel_record.fuel_record')]),
        ]);
    }

    public function destroy(string $vehicleFuelRecord, FuelRecordService $service)
    {
        $vehicleFuelRecord = FuelRecord::findByUuidOrFail($vehicleFuelRecord);

        $this->authorize('delete', $vehicleFuelRecord);

        $service->deletable($vehicleFuelRecord);

        $vehicleFuelRecord->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('transport.vehicle.fuel_record.fuel_record')]),
        ]);
    }

    public function downloadMedia(string $vehicleFuelRecord, string $uuid, FuelRecordService $service)
    {
        $vehicleFuelRecord = FuelRecord::findByUuidOrFail($vehicleFuelRecord);

        $this->authorize('view', $vehicleFuelRecord);

        return $vehicleFuelRecord->downloadMedia($uuid);
    }
}
