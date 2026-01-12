<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class BatchRequest extends FormRequest
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
            'name' => ['required', 'max:100'],
            'course' => 'required',
            'max_strength' => 'nullable|numeric|min:0|max:1000',
            'roll_number_prefix' => 'nullable|min:0|max:20',
            // 'position' => ['required', 'integer', 'min:0', 'max:1000'],
            'pg_account' => ['nullable', 'max:100'],
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('batch');

            $course = Course::query()
                ->byPeriod()
                ->filterAccessible()
                ->where('uuid', $this->course)
                ->getOrFail(trans('academic.course.course'), 'course');

            $existingRecords = Batch::query()
                ->whereCourseId($course->id)
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('academic.batch.props.name')]));
            }

            $this->merge([
                'position' => 0,
                'course_id' => $course->id,
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
            'name' => __('academic.batch.props.name'),
            'course' => __('academic.course.course'),
            'max_strength' => __('academic.batch.props.max_strength'),
            'roll_number_prefix' => __('academic.batch.props.roll_number_prefix'),
            'position' => __('general.position'),
            'pg_account' => __('finance.config.props.pg_account'),
            'description' => __('academic.batch.props.description'),
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
