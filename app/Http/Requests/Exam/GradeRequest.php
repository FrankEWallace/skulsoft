<?php

namespace App\Http\Requests\Exam;

use App\Models\Exam\Grade;
use Illuminate\Foundation\Http\FormRequest;

class GradeRequest extends FormRequest
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
            'name' => ['required', 'min:2', 'max:100'],
            'records' => 'required|array|min:1',
            'records.*.code' => 'required|min:1|max:10|distinct',
            'records.*.min_score' => 'required|numeric|min:0',
            'records.*.max_score' => 'required|numeric|min:0|gt:records.*.min_score',
            'records.*.value' => 'nullable|integer',
            'records.*.label' => 'nullable|max:100',
            'records.*.is_fail_grade' => 'boolean',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('grade.uuid');

            $existingRecords = Grade::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('exam.grade.props.name')]));
            }
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
            'name' => __('exam.grade.props.name'),
            'records' => __('exam.grade.props.records'),
            'records.*.code' => __('exam.grade.props.code'),
            'records.*.min_score' => __('exam.grade.props.min_score'),
            'records.*.max_score' => __('exam.grade.props.max_score'),
            'records.*.value' => __('exam.grade.props.value'),
            'records.*.label' => __('exam.grade.props.label'),
            'records.*.is_fail_grade' => __('exam.grade.props.fail_grade'),
            'records.*.description' => __('exam.grade.props.description'),
            'description' => __('exam.grade.props.description'),
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
