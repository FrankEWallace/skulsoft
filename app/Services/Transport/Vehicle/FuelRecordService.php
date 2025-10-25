<?php

namespace App\Services\Transport\Vehicle;

use App\Http\Resources\Transport\Vehicle\VehicleResource;
use App\Models\Transport\Vehicle\FuelRecord;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Http\Request;

class FuelRecordService
{
    public function preRequisite(Request $request): array
    {
        $vehicles = VehicleResource::collection(Vehicle::query()
            ->byTeam()
            ->get());

        return compact('vehicles');
    }

    public function create(Request $request): FuelRecord
    {
        \DB::beginTransaction();

        $vehicleFuelRecord = FuelRecord::forceCreate($this->formatParams($request));

        $vehicleFuelRecord->addMedia($request);

        \DB::commit();

        return $vehicleFuelRecord;
    }

    private function formatParams(Request $request, ?FuelRecord $vehicleFuelRecord = null): array
    {
        $formatted = [
            'vehicle_id' => $request->vehicle_id,
            'date' => $request->date,
            'quantity' => $request->quantity,
            'price_per_unit' => $request->price_per_unit,
            'log' => $request->log,
            'remarks' => $request->remarks,
        ];

        if (! $vehicleFuelRecord) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, FuelRecord $vehicleFuelRecord): void
    {
        \DB::beginTransaction();

        $vehicleFuelRecord->forceFill($this->formatParams($request, $vehicleFuelRecord))->save();

        $vehicleFuelRecord->updateMedia($request);

        \DB::commit();
    }

    public function deletable(FuelRecord $vehicleFuelRecord): void
    {
        //
    }
}
