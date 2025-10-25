<?php

namespace App\Http\Controllers\Asset\Building;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\Building\FloorRequest;
use App\Http\Resources\Asset\Building\FloorResource;
use App\Models\Asset\Building\Floor;
use App\Services\Asset\Building\FloorListService;
use App\Services\Asset\Building\FloorService;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, FloorService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, FloorListService $service)
    {
        return $service->paginate($request);
    }

    public function store(FloorRequest $request, FloorService $service)
    {
        $floor = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('asset.building.floor.floor')]),
            'floor' => FloorResource::make($floor),
        ]);
    }

    public function show(string $floor, FloorService $service)
    {
        $floor = Floor::findByUuidOrFail($floor);

        return FloorResource::make($floor);
    }

    public function update(FloorRequest $request, string $floor, FloorService $service)
    {
        $floor = Floor::findByUuidOrFail($floor);

        $service->update($request, $floor);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('asset.building.floor.floor')]),
        ]);
    }

    public function destroy(string $floor, FloorService $service)
    {
        $floor = Floor::findByUuidOrFail($floor);

        $service->deletable($floor);

        $floor->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('asset.building.floor.floor')]),
        ]);
    }
}
