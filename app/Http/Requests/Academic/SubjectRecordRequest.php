<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class SubjectRecordRequest extends FormRequest
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
        $rules = [
            'type' => 'required|in:course,batch',
            'batches' => 'required_if:type,batch|array',
            'courses' => 'required_if:type,course|array',
            'credit' => 'required|numeric|min:0|max:100',
            'exam_fee' => 'numeric|min:0|max:100000',
            'course_fee' => 'numeric|min:0|max:100000',
            'max_class_per_week' => 'required|numeric|min:1|max:10',
            'is_elective' => 'boolean',
            'has_no_exam' => 'boolean',
            'has_grading' => 'boolean',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $subject = $this->route('subject');

            $courses = $this->type == 'course' ? Course::query()
                ->byPeriod()
                ->filterAccessible()
                ->whereIn('uuid', $this->courses)
                ->get()
                ->pluck('id')
                ->all() : [];

            $batches = $this->type == 'batch' ? Batch::query()
                ->byPeriod()
                ->filterAccessible()
                ->whereIn('uuid', $this->batches)
                ->get()
                ->pluck('id')
                ->all() : [];

            $this->merge([
                'exam_fee' => is_numeric($this->exam_fee) ? $this->exam_fee : 0,
                'course_fee' => is_numeric($this->course_fee) ? $this->course_fee : 0,
                'batch_ids' => $batches,
                'course_ids' => $courses,
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
            'type' => __('academic.subject.props.type'),
            'courses' => __('academic.course.course'),
            'batches' => __('academic.batch.batch'),
            'credit' => __('academic.subject.props.credit'),
            'exam_fee' => __('academic.subject.props.exam_fee'),
            'course_fee' => __('academic.subject.props.course_fee'),
            'max_class_per_week' => __('academic.subject.props.max_class_per_week'),
            'is_elective' => __('academic.subject.props.is_elective'),
            'has_no_exam' => __('academic.subject.props.has_no_exam'),
            'has_grading' => __('academic.subject.props.has_grading'),
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
            'batches.required_if' => __('validation.required', ['attribute' => __('academic.batch.batch')]),
            'courses.required_if' => __('validation.required', ['attribute' => __('academic.course.course')]),
        ];
    }
}
