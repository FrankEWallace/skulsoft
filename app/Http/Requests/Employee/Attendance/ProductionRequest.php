<?php

namespace App\Http\Requests\Employee\Attendance;

use App\Models\Employee\Attendance\Type as AttendanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ProductionRequest extends FormRequest
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
            'employee' => 'required|uuid',
            'date' => 'required|date',
        ];

        if ($this->has('records')) {
            $rules['records'] = 'required|array|min:1';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            if (! $this->has('records')) {
                return;
            }

            $attendanceTypes = AttendanceType::byTeam()->productionBased()->get();

            foreach ($this->records as $index => $record) {
                if (! in_array(Arr::get($record, 'attendance_type.uuid'), $attendanceTypes->pluck('uuid')->all())) {
                    throw ValidationException::withMessages(['record.'.$index.'attendance_type' => trans('global.could_not_find', ['attribute' => trans('employee.attendance.type.type')])]);
                }
            }

            $this->merge(['attendance_types' => $attendanceTypes]);
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
            'records.*.value' => trans('employee.attendance.props.value'),
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
