<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class ObservationMarkRequest extends FormRequest
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
            $newMarks = [];
            $notApplicableStudents = [];
            foreach ($this->students as $index => $student) {
                $studentUuid = Arr::get($student, 'uuid');

                $comments[] = [
                    'uuid' => $studentUuid,
                    'comment' => Arr::get($student, 'comment'),
                ];

                $isNotApplicable = (bool) Arr::get($student, 'is_not_applicable');

                if ($isNotApplicable) {
                    $notApplicableStudents[] = $studentUuid;
                }

                foreach (Arr::get($student, 'marks', []) as $markIndex => $mark) {

                    if ($isNotApplicable) {
                        $newMarks[] = [
                            'uuid' => $studentUuid,
                            'code' => Arr::get($mark, 'code'),
                            'obtained_mark' => '',
                        ];

                        continue;
                    }

                    $obtainedMark = Arr::get($mark, 'obtained_mark');
                    $maxMark = Arr::get($mark, 'max_mark');

                    if ($obtainedMark == '' || $obtainedMark == null) {
                        $obtainedMark = '';
                    } elseif (is_numeric($obtainedMark)) {
                        if ($obtainedMark < 0) {
                            $validator->errors()->add('students.'.$index.'.marks.'.$markIndex.'.obtained_mark', __('validation.min.numeric', ['attribute' => __('exam.obtained_mark'), 'min' => 0]));
                        } elseif ($obtainedMark > $maxMark) {
                            $validator->errors()->add('students.'.$index.'.marks.'.$markIndex.'.obtained_mark', __('validation.lt.numeric', ['attribute' => __('exam.obtained_mark'), 'value' => $maxMark]));
                        } else {
                            $obtainedMark = round($obtainedMark, 2);
                        }
                    } else {
                        $validator->errors()->add('students.'.$index.'.marks.'.$markIndex.'.obtained_mark', __('validation.exists', ['attribute' => __('exam.obtained_mark')]));
                    }

                    $newMarks[] = [
                        'uuid' => $studentUuid,
                        'code' => Arr::get($mark, 'code'),
                        'comment' => Arr::get($mark, 'comment'),
                        'obtained_mark' => $obtainedMark,
                    ];
                }
            }

            $newMarks = collect($newMarks)->groupBy('code')->map(function ($items, $code) {
                return [
                    'code' => $code,
                    'marks' => $items->map(function ($item) {
                        return [
                            'uuid' => $item['uuid'],
                            'obtained_mark' => $item['obtained_mark'],
                            'comment' => Arr::get($item, 'comment'),
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

            $this->merge([
                'not_applicable_students' => $notApplicableStudents,
                'comments' => $comments,
                'marks' => $newMarks,
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
