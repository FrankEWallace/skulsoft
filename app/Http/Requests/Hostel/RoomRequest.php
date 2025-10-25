<?php

namespace App\Http\Requests\Hostel;

use App\Models\Hostel\Floor;
use App\Models\Hostel\Room;
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
            'capacity' => 'required|integer|min:1|max:100',
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
                ->hostel()
                ->where('floors.uuid', $this->floor)
                ->getOrFail(__('hostel.floor.floor'), 'floor');

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
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('hostel.room.room')]));
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
                $validator->errors()->add('number', trans('validation.unique', ['attribute' => __('hostel.room.room')]));
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
            'name' => __('hostel.room.props.name'),
            'number' => __('hostel.room.props.number'),
            'floor' => __('hostel.floor.floor'),
            'capacity' => __('hostel.room.props.capacity'),
            'description' => __('hostel.room.props.description'),
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
