<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class CommentRequest extends FormRequest
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
            'students' => 'required|array|min:1',
            'students.*.uuid' => 'required|uuid|distinct',
            'students.*.result' => 'nullable|max:200',
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
            $comments = [];
            $notApplicableStudents = [];
            foreach ($this->students as $index => $student) {
                $studentUuid = Arr::get($student, 'uuid');

                $comments[] = [
                    'uuid' => $studentUuid,
                    'result' => Arr::get($student, 'result'),
                    'comment' => Arr::get($student, 'comment'),
                    'incharge_comment' => Arr::get($student, 'incharge_comment'),
                ];

                $isNotApplicable = (bool) Arr::get($student, 'is_not_applicable');

                if ($isNotApplicable) {
                    $notApplicableStudents[] = $studentUuid;
                }
            }

            $this->merge([
                'not_applicable_students' => $notApplicableStudents,
                'comments' => $comments,
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
            'students.*.result' => __('exam.result'),
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
