<?php

namespace App\Services\Transport\Vehicle;

use App\Enums\Transport\Vehicle\FuelType;
use App\Enums\Transport\Vehicle\Ownership;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleService
{
    public function preRequisite(Request $request)
    {
        $fuelTypes = FuelType::getOptions();

        $ownerships = Ownership::getOptions();

        return compact('fuelTypes', 'ownerships');
    }

    public function create(Request $request): Vehicle
    {
        \DB::beginTransaction();

        $vehicle = Vehicle::forceCreate($this->formatParams($request));

        \DB::commit();

        return $vehicle;
    }

    private function formatParams(Request $request, ?Vehicle $vehicle = null): array
    {
        $formatted = [
            'name' => $request->name,
            'registration' => [
                'number' => $request->registration_number,
                'place' => $request->registration_place,
                'date' => $request->registration_date,
                'chassis_number' => $request->chassis_number,
                'engine_number' => $request->engine_number,
                'cubic_capacity' => $request->cubic_capacity,
                'color' => $request->color,
            ],
            'model_number' => $request->model_number,
            'make' => $request->make,
            'class' => $request->class,
            'fuel_type' => $request->fuel_type,
            'fuel_capacity' => $request->fuel_capacity,
            'seating_capacity' => $request->seating_capacity,
            'max_seating_allowed' => $request->max_seating_allowed,
            'owner' => [
                'ownership' => $request->ownership,
                'name' => $request->owner_name,
                'address' => $request->owner_address,
                'phone' => $request->owner_phone,
                'email' => $request->owner_email,
            ],
        ];

        if (! $vehicle) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, Vehicle $vehicle): void
    {
        \DB::beginTransaction();

        $vehicle->forceFill($this->formatParams($request, $vehicle))->save();

        \DB::commit();
    }

    public function deletable(Vehicle $vehicle): bool
    {
        $vehicleDocumentExists = \DB::table('documents')
            ->whereDocumentableType(Vehicle::class)
            ->whereDocumentableId($vehicle->id)
            ->exists();

        if ($vehicleDocumentExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.vehicle.vehicle'), 'dependency' => trans('transport.vehicle.document.document')])]);
        }

        $vehicleTravelRecordExists = \DB::table('vehicle_travel_records')
            ->whereVehicleId($vehicle->id)
            ->exists();

        if ($vehicleTravelRecordExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.vehicle.vehicle'), 'dependency' => trans('transport.vehicle.travel_record.travel_record')])]);
        }

        $vehicleFuelRecordExists = \DB::table('vehicle_fuel_records')
            ->whereVehicleId($vehicle->id)
            ->exists();

        if ($vehicleFuelRecordExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.vehicle.vehicle'), 'dependency' => trans('transport.vehicle.fuel_record.fuel_record')])]);
        }

        $vehicleServiceRecordExists = \DB::table('vehicle_service_records')
            ->whereVehicleId($vehicle->id)
            ->exists();

        if ($vehicleServiceRecordExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.vehicle.vehicle'), 'dependency' => trans('transport.vehicle.service_record.service_record')])]);
        }

        return true;
    }
}
