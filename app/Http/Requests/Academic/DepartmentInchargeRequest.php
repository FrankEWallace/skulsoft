<?php

namespace App\Http\Requests\Academic;

use App\Concerns\HasIncharge;
use App\Models\Academic\Department;
use App\Models\Employee\Employee;
use Illuminate\Foundation\Http\FormRequest;

class DepartmentInchargeRequest extends FormRequest
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
            'department' => 'required|uuid',
            'employee' => 'required|uuid',
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
            $uuid = $this->route('department_incharge');

            $department = Department::query()
                ->byTeam()
                ->where('uuid', $this->department)
                ->getOrFail(trans('academic.department.department'), 'department');

            $employee = Employee::query()
                ->byTeam()
                ->where('uuid', $this->employee)
                ->getOrFail(trans('employee.employee'), 'employee');

            $this->validateInput(employee: $employee, model: $department, uuid: $uuid);

            $this->merge([
                'department_id' => $department->id,
                'employee_id' => $employee->id,
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
            'department' => __('academic.department.department'),
            'employee' => __('employee.employee'),
            'start_date' => __('employee.incharge.props.start_date'),
            'end_date' => __('employee.incharge.props.end_date'),
            'remarks' => __('employee.incharge.props.remarks'),
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
