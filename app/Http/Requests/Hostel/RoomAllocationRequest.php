<?php

namespace App\Http\Requests\Hostel;

use App\Concerns\HasIncharge;
use App\Models\Hostel\Room;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;

class RoomAllocationRequest extends FormRequest
{
    use HasIncharge;

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
            'room' => 'required|uuid',
            'student' => 'required|uuid',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'remarks' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('room_allocation');

            $room = Room::query()
                ->withFloorAndBlock()
                ->hostel()
                ->where('rooms.uuid', $this->room)
                ->getOrFail(__('hostel.room.room'), 'room');

            $student = Student::query()
                ->byTeam()
                ->where('uuid', $this->student)
                ->getOrFail(trans('student.student'), 'student');

            $this->merge([
                'room_id' => $room->id,
                'student_id' => $student->id,
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
            'room' => __('hostel.room.room'),
            'student' => __('student.student'),
            'start_date' => __('hostel.room_allocation.props.start_date'),
            'end_date' => __('hostel.room_allocation.props.end_date'),
            'remarks' => __('hostel.room_allocation.props.remarks'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
