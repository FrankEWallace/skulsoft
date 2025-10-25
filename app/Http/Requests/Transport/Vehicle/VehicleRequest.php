<?php

namespace App\Http\Requests\Transport\Vehicle;

use App\Enums\Transport\Vehicle\FuelType;
use App\Enums\Transport\Vehicle\Ownership;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class VehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'min:2', 'max:100'],
            'registration_number' => ['required', 'min:2', 'max:100'],
            'registration_place' => 'required|min:2|max:100',
            'registration_date' => 'required|date_format:Y-m-d',
            'chassis_number' => 'nullable|min:2|max:100',
            'engine_number' => 'nullable|min:2|max:100',
            'cubic_capacity' => 'nullable|min:1|max:10000',
            'color' => 'nullable|min:2|max:100',
            'model_number' => 'required|min:2|max:100',
            'make' => 'required|min:2|max:100',
            'class' => 'nullable|min:2|max:100',
            'fuel_type' => ['required', new Enum(FuelType::class)],
            'fuel_capacity' => 'required|integer|min:1|max:200',
            'seating_capacity' => 'required|integer|min:1|max:200',
            'max_seating_allowed' => 'required|integer|min:1|max:200',
            'ownership' => ['required', new Enum(Ownership::class)],
            'owner_name' => 'required|min:2|max:100',
            'owner_address' => 'nullable|min:2|max:100',
            'owner_phone' => 'required|min:2|max:100',
            'owner_email' => 'nullable|email',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $vehicleUuid = $this->route('vehicle');

            $existingVehicleName = Vehicle::query()
                ->when($vehicleUuid, fn ($query) => $query->where('uuid', '!=', $vehicleUuid))
                ->where('name', $this->name)
                ->exists();

            if ($existingVehicleName) {
                $validator->errors()->add('name', __('transport.vehicle.duplicate_vehicle'));
            }

            $existingVehicle = Vehicle::query()
                ->when($vehicleUuid, fn ($query) => $query->where('uuid', '!=', $vehicleUuid))
                ->where('registration->number', $this->registration_number)
                ->exists();

            if ($existingVehicle) {
                $validator->errors()->add('registration_number', __('transport.vehicle.duplicate_vehicle'));
            }
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => __('transport.vehicle.props.name'),
            'registration_number' => __('transport.vehicle.props.registration_number'),
            'registration_date' => __('transport.vehicle.props.registration_date'),
            'registration_place' => __('transport.vehicle.props.registration_place'),
            'chassis_number' => __('transport.vehicle.props.chassis_number'),
            'engine_number' => __('transport.vehicle.props.engine_number'),
            'color' => __('transport.vehicle.props.color'),
            'model_number' => __('transport.vehicle.props.model_number'),
            'make' => __('transport.vehicle.props.make'),
            'class' => __('transport.vehicle.props.class'),
            'fuel_type' => __('transport.vehicle.props.fuel_type'),
            'fuel_capacity' => __('transport.vehicle.props.fuel_capacity'),
            'seating_capacity' => __('transport.vehicle.props.seating_capacity'),
            'max_seating_allowed' => __('transport.vehicle.props.max_seating_allowed'),
            'ownership' => __('transport.vehicle.props.ownership'),
            'owner_name' => __('transport.vehicle.props.owner_name'),
            'owner_address' => __('transport.vehicle.props.owner_address'),
            'owner_phone' => __('transport.vehicle.props.owner_phone'),
            'owner_email' => __('transport.vehicle.props.owner_email'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
