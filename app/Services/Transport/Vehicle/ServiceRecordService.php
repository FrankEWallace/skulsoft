<?php

namespace App\Services\Transport\Vehicle;

use App\Http\Resources\Transport\Vehicle\VehicleResource;
use App\Models\Transport\Vehicle\ServiceRecord;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Http\Request;

class ServiceRecordService
{
    public function preRequisite(Request $request): array
    {
        $vehicles = VehicleResource::collection(Vehicle::query()
            ->byTeam()
            ->get());

        return compact('vehicles');
    }

    public function create(Request $request): ServiceRecord
    {
        \DB::beginTransaction();

        $vehicleServiceRecord = ServiceRecord::forceCreate($this->formatParams($request));

        $vehicleServiceRecord->addMedia($request);

        \DB::commit();

        return $vehicleServiceRecord;
    }

    private function formatParams(Request $request, ?ServiceRecord $vehicleServiceRecord = null): array
    {
        $formatted = [
            'vehicle_id' => $request->vehicle_id,
            'date' => $request->date,
            'next_due_date' => $request->next_due_date ?: null,
            'log' => $request->log,
            'next_due_log' => $request->next_due_log ?: null,
            'amount' => $request->amount,
            'remarks' => $request->remarks,
        ];

        if (! $vehicleServiceRecord) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, ServiceRecord $vehicleServiceRecord): void
    {
        \DB::beginTransaction();

        $vehicleServiceRecord->forceFill($this->formatParams($request, $vehicleServiceRecord))->save();

        $vehicleServiceRecord->updateMedia($request);

        \DB::commit();
    }

    public function deletable(ServiceRecord $vehicleServiceRecord): void
    {
        //
    }
}
