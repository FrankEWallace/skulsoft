<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee\Department;
use App\Models\Employee\Designation;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;

class RecordRequest extends FormRequest
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
            'end' => 'boolean',
            'start_date' => 'date_format:Y-m-d',
            'remarks' => 'nullable|min:2|max:1000',
            'department' => 'required|uuid',
            'designation' => 'required|uuid',
            'employment_status' => 'required|uuid',
        ];

        if ($this->method() == 'PATCH') {
            $rules['end'] = 'boolean';
            if ($this->end) {
                $rules['end_date'] = 'required|date_format:Y-m-d';
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $employeeUuid = $this->route('employee');
            $recordUuid = $this->route('record');

            $department = Department::globalOrByTeam()->whereUuid($this->department)->getOrFail(__('employee.department.department'), 'department');

            $designation = Designation::byTeam()->whereUuid($this->designation)->getOrFail(__('employee.designation.designation'), 'designation');

            $employmentStatus = Option::byTeam()->whereType('employment_status')->whereUuid($this->employment_status)->getOrFail(__('employee.employment_status.employment_status'), 'employment_status');

            $this->merge([
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'employment_status_id' => $employmentStatus->id,
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
            'start_date' => __('employee.record.props.start_date'),
            'end_date' => __('employee.record.props.end_date'),
            'end' => __('employee.record.props.end'),
            'department' => __('employee.department.department'),
            'designation' => __('employee.designation.designation'),
            'employment_status' => __('employee.employment_status.employment_status'),
            'remarks' => __('employee.record.props.remarks'),
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
