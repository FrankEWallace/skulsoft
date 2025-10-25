<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class AttendanceRequest extends FormRequest
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
            'exam' => 'required|uuid',
            'batch' => 'required|uuid',
            'total_working_days' => 'required|integer',
            'students' => 'required|array|min:1',
            'students.*.uuid' => 'required|uuid|distinct',
            'students.*.attendance' => 'nullable|integer',
            'students.*.comment' => 'nullable|max:200',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $attendances = [];
            $notApplicableStudents = [];
            foreach ($this->students as $index => $student) {
                $studentUuid = Arr::get($student, 'uuid');

                $attendance = Arr::get($student, 'attendance');
                if ($attendance && $attendance > $this->total_working_days) {
                    $validator->errors()->add("students.{$index}.attendance", __('validation.max.numeric', ['attribute' => __('student.attendance.attendance'), 'max' => $this->total_working_days]));
                }

                $attendances[] = [
                    'uuid' => $studentUuid,
                    'attendance' => Arr::get($student, 'attendance'),
                    'comment' => Arr::get($student, 'comment'),
                ];

                $isNotApplicable = (bool) Arr::get($student, 'is_not_applicable');

                if ($isNotApplicable) {
                    $notApplicableStudents[] = $studentUuid;
                }
            }

            $this->merge([
                'not_applicable_students' => $notApplicableStudents,
                'attendances' => $attendances,
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
            'exam' => __('exam.exam'),
            'batch' => __('academic.batch.batch'),
            'grade' => __('exam.grade.grade'),
            'students.*.comment' => __('exam.comment'),
            'students.*.attendance' => __('student.attendance.attendance'),
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
