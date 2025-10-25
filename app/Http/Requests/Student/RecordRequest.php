<?php

namespace App\Http\Requests\Student;

use App\Enums\OptionType;
use App\Models\Academic\Batch;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;

class RecordRequest extends FormRequest
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
            'edit_code_number' => 'boolean',
            'joining_date' => 'required_if:edit_code_number,true|date_format:Y-m-d|before_or_equal:start_date',
            'start_date' => 'required_if:edit_code_number,true|date_format:Y-m-d',
            'code_number' => 'required_if:edit_code_number,true|min:1|max:100',
            'code_number_format' => 'nullable|min:1|max:50',
            'edit_batch' => 'boolean',
            'batch' => 'required_if:edit_batch,true|uuid',
            'edit_course' => 'boolean',
            'course_batch' => 'required_if:edit_course,true|uuid',
            'enrollment_type' => 'nullable|uuid',
            'enrollment_status' => 'nullable|uuid',
            'remarks' => 'nullable|min:2|max:1000',
        ];

        if ($this->convert_to_regular) {
            $rules['joining_date'] = 'required|date_format:Y-m-d';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $employeeUuid = $this->route('employee');
            $recordUuid = $this->route('record');

            $batch = $this->batch ? Batch::query()
                ->byPeriod()
                ->filterAccessible()
                ->whereUuid($this->batch)
                ->getOrFail(trans('academic.batch.batch')) : null;

            $courseBatch = $this->course_batch ? Batch::query()
                ->with('course')
                ->byPeriod()
                ->filterAccessible()
                ->whereUuid($this->course_batch)
                ->getOrFail(trans('academic.batch.batch')) : null;

            $enrollmentType = $this->enrollment_type ? Option::query()
                ->byTeam()
                ->where('type', OptionType::STUDENT_ENROLLMENT_TYPE->value)
                ->whereUuid($this->enrollment_type)
                ->getOrFail(trans('student.enrollment_type.enrollment_type')) : null;

            $enrollmentStatus = $this->enrollment_status ? Option::query()
                ->byTeam()
                ->where('type', OptionType::STUDENT_ENROLLMENT_STATUS->value)
                ->whereUuid($this->enrollment_status)
                ->getOrFail(trans('student.enrollment_status.enrollment_status')) : null;

            $this->merge([
                'batch' => $batch,
                'course_batch' => $courseBatch,
                'enrollment_type_id' => $enrollmentType?->id,
                'enrollment_status_id' => $enrollmentStatus?->id,
                'enrollment_status' => $enrollmentStatus,
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
            'edit_code_number' => __('global.edit', ['attribute' => __('student.admission.props.code_number')]),
            'joining_date' => __('student.admission.props.date'),
            'start_date' => __('student.record.props.promotion_date'),
            'code_number' => __('student.admission.props.code_number'),
            'code_number_format' => __('student.admission.props.code_number_format'),
            'edit_batch' => __('global.edit', ['attribute' => __('academic.batch.batch')]),
            'batch' => __('academic.batch.batch'),
            'edit_course' => __('global.edit', ['attribute' => __('academic.course.course')]),
            'enrollment_type' => __('student.enrollment_type.enrollment_type'),
            'enrollment_status' => __('student.enrollment_status.enrollment_status'),
            'remarks' => __('student.record.props.remarks'),
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
            'code_number.required_if' => __('validation.required', ['attribute' => __('student.admission.props.code_number')]),
            'code_number_format.required_if' => __('validation.required', ['attribute' => __('student.admission.props.code_number_format')]),
            'code_number_sno.required_if' => __('validation.required', ['attribute' => __('student.admission.props.code_number_sno')]),
            'batch.required_if' => __('validation.required', ['attribute' => __('academic.batch.batch')]),
        ];
    }
}
