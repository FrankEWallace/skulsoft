<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class HealthRecordRequest extends FormRequest
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
            'students.*.height' => ['nullable', 'numeric', 'min:1'],
            'students.*.weight' => ['nullable', 'numeric', 'min:1'],
            'students.*.chest' => ['nullable', 'numeric', 'min:1'],
            'students.*.left_eye' => ['nullable', 'numeric'],
            'students.*.right_eye' => ['nullable', 'numeric'],
            'students.*.dental_hygiene' => ['nullable', 'max:100'],
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
            'students.*.height' => trans('student.health_record.props.height'),
            'students.*.weight' => trans('student.health_record.props.weight'),
            'students.*.chest' => trans('student.health_record.props.chest'),
            'students.*.left_eye' => trans('student.health_record.props.left_eye'),
            'students.*.right_eye' => trans('student.health_record.props.right_eye'),
            'students.*.dental_hygiene' => trans('student.health_record.props.dental_hygiene'),
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
