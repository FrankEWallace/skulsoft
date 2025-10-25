<?php

namespace App\Http\Requests\Employee\Attendance;

use App\Models\Employee\Attendance\WorkShift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class WorkShiftAssignRequest extends FormRequest
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
            $workShifts = WorkShift::byTeam()->get();

            $employees = [];
            foreach ($this->employees as $index => $employee) {
                if (Arr::get($employee, 'work_shift')) {
                    $workShift = $workShifts->firstWhere('uuid', Arr::get($employee, 'work_shift'));

                    if (! $workShift) {
                        $validator->errors()->add('employees.'.$index.'.work_shift', trans('global.could_not_find', ['attribute' => trans('employee.attendance.work_shift.work_shift')]));
                    } else {
                        $employee['work_shift_id'] = $workShift->id;
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
