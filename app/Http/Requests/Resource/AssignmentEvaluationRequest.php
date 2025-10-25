<?php

namespace App\Http\Requests\Resource;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentEvaluationRequest extends FormRequest
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
            'students' => 'required|array',
            'students.*.uuid' => 'required|uuid',
            'students.*.obtained_mark' => 'required|numeric|min:0',
            'students.*.comment' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {});
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'students.*.uuid' => trans('student.student'),
            'students.*.obtained_mark' => trans('resource.assignment.props.obtained_mark'),
            'students.*.comment' => trans('resource.assignment.props.comment'),
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
