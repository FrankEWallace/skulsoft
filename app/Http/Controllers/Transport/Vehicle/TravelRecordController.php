<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\Vehicle\TravelRecordRequest;
use App\Http\Resources\Transport\Vehicle\TravelRecordResource;
use App\Models\Transport\Vehicle\TravelRecord;
use App\Services\Transport\Vehicle\TravelRecordListService;
use App\Services\Transport\Vehicle\TravelRecordService;
use Illuminate\Http\Request;

class TravelRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, TravelRecordService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, TravelRecordListService $service)
    {
        $this->authorize('viewAny', TravelRecord::class);

        return $service->paginate($request);
    }

    public function store(TravelRecordRequest $request, TravelRecordService $service)
    {
        $this->authorize('create', TravelRecord::class);

        $vehicleTravelRecord = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('transport.vehicle.travel_record.travel_record')]),
            'vehicle' => TravelRecordResource::make($vehicleTravelRecord),
        ]);
    }

    public function show(string $vehicleTravelRecord, TravelRecordService $service)
    {
        $vehicleTravelRecord = TravelRecord::findByUuidOrFail($vehicleTravelRecord);

        $this->authorize('view', $vehicleTravelRecord);

        $vehicleTravelRecord->load('vehicle', 'media');

        return TravelRecordResource::make($vehicleTravelRecord);
    }

    public function update(TravelRecordRequest $request, string $vehicleTravelRecord, TravelRecordService $service)
    {
        $vehicleTravelRecord = TravelRecord::findByUuidOrFail($vehicleTravelRecord);

        $this->authorize('update', $vehicleTravelRecord);

        $service->update($request, $vehicleTravelRecord);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('transport.vehicle.travel_record.travel_record')]),
        ]);
    }

    public function destroy(string $vehicleTravelRecord, TravelRecordService $service)
    {
        $vehicleTravelRecord = TravelRecord::findByUuidOrFail($vehicleTravelRecord);

        $this->authorize('delete', $vehicleTravelRecord);

        $service->deletable($vehicleTravelRecord);

        $vehicleTravelRecord->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('transport.vehicle.travel_record.travel_record')]),
        ]);
    }

    public function downloadMedia(string $vehicleTravelRecord, string $uuid, TravelRecordService $service)
    {
        $vehicleTravelRecord = TravelRecord::findByUuidOrFail($vehicleTravelRecord);

        $this->authorize('view', $vehicleTravelRecord);

        return $vehicleTravelRecord->downloadMedia($uuid);
    }
}
