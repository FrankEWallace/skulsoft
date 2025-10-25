<?php

namespace App\Http\Requests\Academic;

use App\Enums\OptionType;
use App\Models\Academic\Course;
use App\Models\Academic\EnrollmentSeat;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;

class EnrollmentSeatRequest extends FormRequest
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
            'enrollment_type' => ['required', 'uuid'],
            'course' => ['required', 'uuid'],
            'max_seat' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'min:2', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('enrollment_seat');

            $enrollmentType = Option::query()
                ->byTeam()
                ->where('type', OptionType::STUDENT_ENROLLMENT_TYPE)
                ->where('uuid', $this->enrollment_type)
                ->getOrFail(trans('student.enrollment_type.enrollment_type'), 'enrollment_type');

            $course = Course::query()
                ->byPeriod()
                ->filterAccessible()
                ->where('uuid', $this->course)
                ->getOrFail(trans('academic.course.course'), 'course');

            $existingRecords = EnrollmentSeat::query()
                ->where('course_id', $course?->id)
                ->where('enrollment_type_id', $enrollmentType?->id)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('enrollment_type', trans('global.duplicate', ['attribute' => trans('academic.enrollment_seat.enrollment_seat')]));
            }

            $this->merge([
                'position' => 0,
                'course_id' => $course?->id,
                'enrollment_type_id' => $enrollmentType?->id,
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
            'enrollment_type' => __('student.enrollment_type.enrollment_type'),
            'course' => __('academic.course.course'),
            'max_seat' => __('academic.enrollment_seat.props.max_seat'),
            'description' => __('academic.enrollment_seat.props.description'),
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
