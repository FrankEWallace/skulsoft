<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class TimesheetBatchRequest extends FormRequest
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
            'students' => ['required', 'array', 'min:1'],
            'students.*.in_at' => ['nullable', 'date_format:H:i:s'],
            'students.*.out_at' => ['nullable', 'date_format:H:i:s'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            //
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
            'students' => trans('student.student'),
            'students.*.in_at' => trans('student.timesheet.props.in_at'),
            'students.*.out_at' => trans('student.timesheet.props.out_at'),
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
