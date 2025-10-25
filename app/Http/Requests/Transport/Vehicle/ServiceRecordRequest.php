<?php

namespace App\Http\Requests\Transport\Vehicle;

use App\Models\Transport\Vehicle\ServiceRecord;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

class ServiceRecordRequest extends FormRequest
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
            'log' => 'required|numeric|min:0',
            'next_due_log' => 'nullable|numeric|gt:log',
            'date' => 'required|date',
            'next_due_date' => 'nullable|date|after:date',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new ServiceRecord)->getModelName();

            $vehicleServiceRecordUuid = $this->route('service_record');

            $vehicle = Vehicle::query()
                ->byTeam()
                ->whereUuid($this->vehicle)
                ->getOrFail(__('transport.vehicle.vehicle'), 'vehicle');

            $this->merge([
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
            'log' => __('transport.vehicle.service_record.props.log'),
            'date' => __('transport.vehicle.service_record.props.date'),
            'remarks' => __('transport.vehicle.service_record.props.remarks'),
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
