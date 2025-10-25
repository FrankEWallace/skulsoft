<?php

namespace App\Http\Requests\Asset\Building;

use App\Models\Asset\Building\Floor;
use App\Models\Asset\Building\Room;
use Illuminate\Foundation\Http\FormRequest;

class RoomRequest extends FormRequest
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
            'name' => 'required|min:2|max:100',
            'number' => 'required|min:1|max:20',
            'floor' => 'required|uuid',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('room');

            $floor = Floor::query()
                ->withBlock()
                ->notAHostel()
                ->where('floors.uuid', $this->floor)
                ->getOrFail(__('asset.building.floor.floor'), 'floor');

            $this->merge([
                'floor_id' => $floor->id,
            ]);

            $existingNames = Room::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereFloorId($floor->id)
                ->whereName($this->name)
                ->exists();

            if ($existingNames) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('asset.building.room.room')]));
            }

            $existingNumbers = Room::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereFloorId($floor->id)
                ->whereNumber($this->number)
                ->exists();

            if ($existingNumbers) {
                $validator->errors()->add('number', trans('validation.unique', ['attribute' => __('asset.building.room.room')]));
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
            'name' => __('asset.building.room.props.name'),
            'number' => __('asset.building.room.props.number'),
            'floor' => __('asset.building.floor.floor'),
            'description' => __('asset.building.room.props.description'),
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
