<?php

namespace App\Http\Requests\Academic;

use App\Concerns\HasIncharge;
use App\Models\Academic\Program;
use App\Models\Employee\Employee;
use Illuminate\Foundation\Http\FormRequest;

class ProgramInchargeRequest extends FormRequest
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
            'program' => 'required|uuid',
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
            $uuid = $this->route('program_incharge');

            $program = Program::query()
                ->byTeam()
                ->where('uuid', $this->program)
                ->getOrFail(trans('academic.program.program'), 'program');

            $employee = Employee::query()
                ->byTeam()
                ->where('uuid', $this->employee)
                ->getOrFail(trans('employee.employee'), 'employee');

            $this->validateInput(employee: $employee, model: $program, uuid: $uuid);

            $this->merge([
                'program_id' => $program->id,
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
            'program' => __('academic.program.program'),
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
