<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hostel\RoomRequest;
use App\Http\Resources\Hostel\RoomResource;
use App\Models\Hostel\Room;
use App\Services\Hostel\RoomListService;
use App\Services\Hostel\RoomService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, RoomService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, RoomListService $service)
    {
        return $service->paginate($request);
    }

    public function store(RoomRequest $request, RoomService $service)
    {
        $room = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('hostel.room.room')]),
            'room' => RoomResource::make($room),
        ]);
    }

    public function show(string $room, RoomService $service)
    {
        $room = Room::findByUuidOrFail($room);

        return RoomResource::make($room);
    }

    public function update(RoomRequest $request, string $room, RoomService $service)
    {
        $room = Room::findByUuidOrFail($room);

        $service->update($request, $room);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('hostel.room.room')]),
        ]);
    }

    public function destroy(string $room, RoomService $service)
    {
        $room = Room::findByUuidOrFail($room);

        $service->deletable($room);

        $room->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('hostel.room.room')]),
        ]);
    }
}
