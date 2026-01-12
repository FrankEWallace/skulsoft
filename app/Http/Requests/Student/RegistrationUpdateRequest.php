<?php

namespace App\Http\Requests\Student;

use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Period;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;

class RegistrationUpdateRequest extends FormRequest
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
            'date' => 'required|date_format:Y-m-d',
            'period' => 'required|uuid',
            'course' => 'required|uuid',
            'enrollment_type' => 'nullable|uuid',
            'registration_fee' => 'required|numeric|min:0',
            'payment_due_date' => 'nullable|date_format:Y-m-d',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('registration');

            $period = Period::query()
                ->byTeam()
                ->whereUuid($this->period)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('academic.period.period')]), 'period');

            $course = Course::query()
                ->byPeriod($period->id)
                ->whereUuid($this->course)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('academic.course.course')]), 'course');

            $enrollmentType = $this->enrollment_type ? Option::query()
                ->byTeam()
                ->whereUuid($this->enrollment_type)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('student.enrollment_type.enrollment_type')]), 'enrollment_type') : null;

            if (! $course->enable_registration) {
                $validator->errors()->add('course', trans('academic.course.registration_disabled_info'));
            }

            $registrationFee = $course->enable_registration ? $course->registration_fee->value : 0;

            if (auth()->user()->can('registration:action') && $this->registration_fee != $registrationFee) {
                $registrationFee = $this->registration_fee;
            }

            $this->merge([
                'period_id' => $period?->id,
                'course_id' => $course?->id,
                'enrollment_type_id' => $enrollmentType?->id,
                'registration_fee' => $registrationFee,
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
            'period' => __('academic.period.period'),
            'course' => __('academic.course.course'),
            'date' => __('student.registration.props.date'),
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
