<?php

namespace App\Http\Requests\Employee\Attendance;

use App\Models\Employee\Attendance\Type as AttendanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class AttendanceRequest extends FormRequest
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
        return [];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $attendanceTypes = AttendanceType::byTeam()->direct()->get();

            $employeeUuids = collect($this->employees)->pluck('uuid')->all();

            $employees = [];
            foreach ($this->employees as $index => $employee) {
                if (Arr::get($employee, 'attendance_type')) {
                    $attendanceType = $attendanceTypes->firstWhere('uuid', Arr::get($employee, 'attendance_type'));

                    if (! $attendanceType) {
                        $validator->errors()->add('employees.'.$index.'.attendance_type', trans('global.could_not_find', ['attribute' => trans('employee.attendance.type.type')]));
                    } else {
                        $employee['attendance_type_id'] = $attendanceType->id;
                    }
                }

                $employees[] = $employee;
            }

            $this->merge(['employees' => $employees]);
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
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
