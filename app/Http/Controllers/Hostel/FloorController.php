<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hostel\FloorRequest;
use App\Http\Resources\Hostel\FloorResource;
use App\Models\Hostel\Floor;
use App\Services\Hostel\FloorListService;
use App\Services\Hostel\FloorService;
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
            'message' => trans('global.created', ['attribute' => trans('hostel.floor.floor')]),
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
            'message' => trans('global.updated', ['attribute' => trans('hostel.floor.floor')]),
        ]);
    }

    public function destroy(string $floor, FloorService $service)
    {
        $floor = Floor::findByUuidOrFail($floor);

        $service->deletable($floor);

        $floor->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('hostel.floor.floor')]),
        ]);
    }
}
