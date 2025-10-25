<?php

namespace App\Services\Hostel;

use App\Http\Resources\Hostel\RoomResource;
use App\Models\Hostel\Room;
use App\Models\Hostel\RoomAllocation;
use Illuminate\Http\Request;

class RoomAllocationService
{
    public function preRequisite(Request $request)
    {
        $rooms = RoomResource::collection(Room::query()
            ->withFloorAndBlock()
            ->hostel()
            ->get());

        return compact('rooms');
    }

    public function create(Request $request): RoomAllocation
    {
        \DB::beginTransaction();

        $roomAllocation = RoomAllocation::forceCreate($this->formatParams($request));

        \DB::commit();

        return $roomAllocation;
    }

    private function formatParams(Request $request, ?RoomAllocation $roomAllocation = null): array
    {
        $formatted = [
            'model_type' => 'Student',
            'model_id' => $request->student_id,
            'room_id' => $request->room_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ];

        if (! $roomAllocation) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, RoomAllocation $roomAllocation): void
    {
        \DB::beginTransaction();

        $roomAllocation->forceFill($this->formatParams($request, $roomAllocation))->save();

        \DB::commit();
    }

    public function deletable(RoomAllocation $roomAllocation): void
    {
        //
    }
}
