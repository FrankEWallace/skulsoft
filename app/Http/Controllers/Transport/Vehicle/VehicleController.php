<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\Vehicle\VehicleRequest;
use App\Http\Resources\Transport\Vehicle\VehicleResource;
use App\Models\Transport\Vehicle\Vehicle;
use App\Services\Transport\Vehicle\VehicleListService;
use App\Services\Transport\Vehicle\VehicleService;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, VehicleService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, VehicleListService $service)
    {
        $this->authorize('viewAny', Vehicle::class);

        return $service->paginate($request);
    }

    public function store(VehicleRequest $request, VehicleService $service)
    {
        $this->authorize('create', Vehicle::class);

        $vehicle = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('transport.vehicle.vehicle')]),
            'vehicle' => VehicleResource::make($vehicle),
        ]);
    }

    public function show(string $vehicle, VehicleService $service)
    {
        $vehicle = Vehicle::findByUuidOrFail($vehicle);

        $this->authorize('view', $vehicle);

        return VehicleResource::make($vehicle);
    }

    public function update(VehicleRequest $request, string $vehicle, VehicleService $service)
    {
        $vehicle = Vehicle::findByUuidOrFail($vehicle);

        $this->authorize('update', $vehicle);

        $service->update($request, $vehicle);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('transport.vehicle.vehicle')]),
        ]);
    }

    public function destroy(string $vehicle, VehicleService $service)
    {
        $vehicle = Vehicle::findByUuidOrFail($vehicle);

        $this->authorize('delete', $vehicle);

        $service->deletable($vehicle);

        $vehicle->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('transport.vehicle.vehicle')]),
        ]);
    }
}
