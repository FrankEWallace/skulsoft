<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class CourseBatchRequest extends FormRequest
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
            'batches' => ['required', 'array', 'min:1'],
            'batches.*.name' => ['required', 'max:100', 'distinct'],
            'batches.*.max_strength' => 'nullable|numeric|min:0|max:1000',
            'batches.*.roll_number_prefix' => 'nullable|min:0|max:20',
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
            'batches.*.name' => __('academic.batch.props.name'),
            'batches.*.max_strength' => __('academic.batch.props.max_strength'),
            'batches.*.roll_number_prefix' => __('academic.batch.props.roll_number_prefix'),
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
