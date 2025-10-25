<?php

namespace App\Http\Requests\Transport\Vehicle;

use App\Models\Transport\Vehicle\FuelRecord;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

class FuelRecordRequest extends FormRequest
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
            'vehicle' => 'required',
            'quantity' => 'required|numeric|min:0.01',
            'price_per_unit' => 'required|numeric|min:0',
            'log' => 'required|numeric|min:0',
            'date' => 'required|date',
            'remarks' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new FuelRecord)->getModelName();

            $vehicleFuelRecordUuid = $this->route('fuel_record');

            $vehicle = Vehicle::query()
                ->byTeam()
                ->whereUuid($this->vehicle)
                ->getOrFail(__('transport.vehicle.vehicle'), 'vehicle');

            $this->merge([
                'fuel_type' => $vehicle->fuel_type->value,
                'vehicle_id' => $vehicle->id,
            ]);
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
            'vehicle' => __('transport.vehicle.vehicle'),
            'quantity' => __('transport.vehicle.fuel_record.props.quantity'),
            'price_per_unit' => __('transport.vehicle.fuel_record.props.price_per_unit'),
            'log' => __('transport.vehicle.fuel_record.props.log'),
            'date' => __('transport.vehicle.fuel_record.props.date'),
            'remarks' => __('transport.vehicle.fuel_record.props.remarks'),
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
