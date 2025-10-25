<?php

namespace App\Services\Transport\Vehicle;

use App\Http\Resources\Transport\Vehicle\VehicleResource;
use App\Models\Transport\Vehicle\TravelRecord;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Http\Request;

class TravelRecordService
{
    public function preRequisite(Request $request): array
    {
        $vehicles = VehicleResource::collection(Vehicle::query()
            ->byTeam()
            ->get());

        return compact('vehicles');
    }

    public function create(Request $request): TravelRecord
    {
        \DB::beginTransaction();

        $vehicleTravelRecord = TravelRecord::forceCreate($this->formatParams($request));

        $vehicleTravelRecord->addMedia($request);

        \DB::commit();

        return $vehicleTravelRecord;
    }

    private function formatParams(Request $request, ?TravelRecord $vehicleTravelRecord = null): array
    {
        $formatted = [
            'vehicle_id' => $request->vehicle_id,
            'date' => $request->date,
            'log' => $request->log,
            'remarks' => $request->remarks,
        ];

        if (! $vehicleTravelRecord) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, TravelRecord $vehicleTravelRecord): void
    {
        \DB::beginTransaction();

        $vehicleTravelRecord->forceFill($this->formatParams($request, $vehicleTravelRecord))->save();

        $vehicleTravelRecord->updateMedia($request);

        \DB::commit();
    }

    public function deletable(TravelRecord $vehicleTravelRecord): void
    {
        //
    }
}
