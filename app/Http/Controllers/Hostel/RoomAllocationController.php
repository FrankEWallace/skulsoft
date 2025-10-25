<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hostel\RoomAllocationRequest;
use App\Http\Resources\Hostel\RoomAllocationResource;
use App\Models\Hostel\RoomAllocation;
use App\Services\Hostel\RoomAllocationListService;
use App\Services\Hostel\RoomAllocationService;
use Illuminate\Http\Request;

class RoomAllocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, RoomAllocationService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, RoomAllocationListService $service)
    {
        $this->authorize('viewAny', RoomAllocation::class);

        return $service->paginate($request);
    }

    public function store(RoomAllocationRequest $request, RoomAllocationService $service)
    {
        $this->authorize('create', RoomAllocation::class);

        $roomAllocation = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('hostel.room_allocation.room_allocation')]),
            'room_allocation' => RoomAllocationResource::make($roomAllocation),
        ]);
    }

    public function show(string $roomAllocation, RoomAllocationService $service)
    {
        $roomAllocation = RoomAllocation::findByUuidOrFail($roomAllocation);

        $this->authorize('view', $roomAllocation);

        $roomAllocation->load([
            'room' => fn ($q) => $q->withFloorAndBlock(),
            'model' => fn ($q) => $q->summary(),
        ]);

        return RoomAllocationResource::make($roomAllocation);
    }

    public function update(RoomAllocationRequest $request, string $roomAllocation, RoomAllocationService $service)
    {
        $roomAllocation = RoomAllocation::findByUuidOrFail($roomAllocation);

        $this->authorize('update', $roomAllocation);

        $service->update($request, $roomAllocation);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('hostel.room_allocation.room_allocation')]),
        ]);
    }

    public function destroy(string $roomAllocation, RoomAllocationService $service)
    {
        $roomAllocation = RoomAllocation::findByUuidOrFail($roomAllocation);

        $this->authorize('delete', $roomAllocation);

        $service->deletable($roomAllocation);

        $roomAllocation->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('hostel.room_allocation.room_allocation')]),
        ]);
    }
}
