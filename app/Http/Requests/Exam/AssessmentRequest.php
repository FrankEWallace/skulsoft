<?php

namespace App\Http\Requests\Exam;

use App\Models\Exam\Assessment;
use Illuminate\Foundation\Http\FormRequest;

class AssessmentRequest extends FormRequest
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
            'records.*.name' => 'required|min:1|max:100|distinct',
            'records.*.code' => 'required|min:1|max:10|distinct',
            'records.*.max_mark' => 'required|numeric|min:0',
            'records.*.passing_mark' => 'required|numeric|min:0|max:100',
            'records.*.description' => 'nullable|max:1000',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('assessment.uuid');

            $existingRecords = Assessment::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('exam.assessment.props.name')]));
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
            'name' => __('exam.assessment.props.name'),
            'records' => __('exam.assessment.props.type'),
            'records.*.name' => __('exam.assessment.props.name'),
            'records.*.code' => __('exam.assessment.props.code'),
            'records.*.max_mark' => __('exam.assessment.props.max_mark'),
            'records.*.passing_mark' => __('exam.assessment.props.passing_mark'),
            'records.*.description' => __('exam.assessment.props.description'),
            'description' => __('exam.assessment.props.description'),
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
