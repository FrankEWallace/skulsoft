<?php

namespace App\Http\Requests\Exam;

use App\Models\Exam\Grade;
use App\Models\Exam\Observation;
use Illuminate\Foundation\Http\FormRequest;

class ObservationRequest extends FormRequest
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
            'grade' => 'required|uuid',
            'records' => 'required|array|min:1',
            'records.*.name' => 'required|min:1|max:50|distinct',
            'records.*.code' => 'required|min:1|max:10|distinct',
            'records.*.max_mark' => 'required|numeric|min:0',
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
            $uuid = $this->route('observation');

            $grade = Grade::query()
                ->byPeriod()
                ->whereUuid($this->grade)
                ->getOrFail(trans('exam.grade.grade'), 'grade');

            $existingRecords = Observation::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('exam.observation.props.name')]));
            }

            $this->merge([
                'grade_id' => $grade->id,
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
            'name' => __('exam.observation.props.name'),
            'grade' => __('exam.grade.grade'),
            'records' => __('exam.observation.props.records'),
            'records.*.name' => __('exam.observation.props.name'),
            'records.*.code' => __('exam.observation.props.code'),
            'records.*.max_mark' => __('exam.observation.props.max_mark'),
            'records.*.description' => __('exam.observation.props.description'),
            'description' => __('exam.observation.props.description'),
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
