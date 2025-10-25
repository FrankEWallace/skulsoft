<?php

namespace App\Http\Requests\Transport;

use App\Enums\Transport\Direction;
use App\Helpers\CalHelper;
use App\Models\Transport\Route;
use App\Models\Transport\Stoppage;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class RouteRequest extends FormRequest
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
        $rules = [
            'direction' => ['required', new Enum(Direction::class)],
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'max_capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'vehicle' => ['nullable', 'uuid'],
            'stoppages' => ['required', 'array', 'min:1'],
            'stoppages.*.stoppage' => ['required', 'uuid', 'distinct'],
            'stoppages.*.arrival_time' => 'required|integer|min:0,max:1000',
            'duration_to_destination' => ['required', 'integer', 'min:0', 'max:1000'],
            'description' => 'nullable|string|max:1000',
        ];

        if ($this->direction == 'arrival' || $this->direction == 'roundtrip') {
            $rules['arrival_starts_at'] = ['required', 'date_format:H:i:s'];
        }

        if ($this->direction == 'departure' || $this->direction == 'roundtrip') {
            $rules['departure_starts_at'] = ['required', 'date_format:H:i:s'];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('route');

            $vehicle = Vehicle::query()
                ->byTeam()
                ->whereUuid($this->vehicle)
                ->getOrFail(__('transport.vehicle.vehicle'), 'vehicle');

            $existingRecords = Route::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('transport.route.route')]));
            }

            $existingRecordWithTime = Route::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereVehicleId($vehicle?->id)
                ->where(function ($q) {
                    $q->where('arrival_starts_at', CalHelper::storeDateTime($this->arrival_starts_at)?->toTimeString())
                        ->orWhere('departure_starts_at', CalHelper::storeDateTime($this->departure_starts_at)?->toTimeString());
                })
                ->exists();

            if ($existingRecordWithTime) {
                $validator->errors()->add('vehicle', trans('transport.route.duplicate_route_with_same_vehicle_and_time'));
            }

            $stoppages = Stoppage::query()
                ->byPeriod()
                ->get();

            $newStoppages = [];
            foreach ($this->stoppages as $index => $item) {
                $stoppage = $stoppages->firstWhere('uuid', Arr::get($item, 'stoppage'));

                if (! $stoppage) {
                    throw ValidationException::withMessages(['stoppages.'.$index.'.stoppage' => trans('global.could_not_find', ['attribute' => trans('transport.stoppage.stoppage')])]);
                }

                $arrivalTime = Arr::get($item, 'arrival_time');

                $newStoppages[] = Arr::add($item, 'id', $stoppage->id);
            }

            $this->merge([
                'vehicle_id' => $vehicle?->id,
                'arrival_starts_at' => $this->direction == 'arrival' || $this->direction == 'roundtrip' ? $this->arrival_starts_at : null,
                'departure_starts_at' => $this->direction == 'departure' || $this->direction == 'roundtrip' ? $this->departure_starts_at : null,
                'stoppages' => $newStoppages,
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
            'name' => __('transport.route.props.name'),
            'max_capacity' => __('transport.route.props.max_capacity'),
            'vehicle' => __('transport.vehicle.vehicle'),
            'stoppages.*.stoppage' => __('transport.stoppage.stoppage'),
            'stoppages.*.arrival_time' => __('transport.route.props.arrival_time'),
            'duration_to_destination' => __('transport.route.props.duration_to_destination'),
            'description' => __('transport.route.props.description'),
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
