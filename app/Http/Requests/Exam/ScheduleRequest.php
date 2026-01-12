<?php

namespace App\Http\Requests\Exam;

use App\Enums\Exam\AssessmentAttempt;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Subject;
use App\Models\Exam\Assessment;
use App\Models\Exam\Exam;
use App\Models\Exam\Grade;
use App\Models\Exam\Observation;
use App\Models\Exam\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ScheduleRequest extends FormRequest
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
            'is_reassessment' => 'boolean',
            'grade' => 'required|uuid',
            'assessment' => 'required|uuid',
            'observation' => 'nullable|uuid',
            'records' => 'required|array|min:1',
            'records.*.uuid' => 'required|uuid|distinct',
            'records.*.subject.uuid' => 'required|uuid|distinct',
            'records.*.has_exam' => 'boolean',
            'records.*.assessments' => 'required|array',
            'records.*.assessments.*.code' => 'required',
            'records.*.assessments.*.marks' => 'required_if:records.*.has_exam,true|regex:/^[0-9]*\/?[0-9]*$/',
            'records.*.date' => 'nullable|date_format:Y-m-d',
            'records.*.start_time' => 'nullable|date_format:H:i:s',
            'records.*.duration' => 'nullable|integer|min:1|max:1440',
            'additional_subjects' => 'array',
            'additional_subjects.*.name' => 'required|string|max:50|distinct',
            'additional_subjects.*.code' => 'nullable|distinct',
            'additional_subjects.*.date' => 'required|date_format:Y-m-d',
            'additional_subjects.*.start_time' => 'nullable|date_format:H:i:s',
            'additional_subjects.*.duration' => 'nullable|integer|min:1|max:1440',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('schedule');

            $batch = Batch::query()
                ->byPeriod()
                ->filterAccessible()
                ->whereUuid($this->batch)
                ->getOrFail(trans('academic.batch.batch'), 'batch');

            $exam = Exam::query()
                ->byPeriod()
                ->where(function ($q) use ($batch) {
                    $q->whereDoesntHave('term')
                        ->orWhereHas('term', function ($q) use ($batch) {
                            $q->whereNull('division_id')->orWhere('division_id', $batch->course->division_id);
                        });
                })
                ->whereUuid($this->exam)
                ->getOrFail(trans('exam.exam'), 'exam');

            $grade = Grade::query()
                ->byPeriod()
                ->whereUuid($this->grade)
                ->getOrFail(trans('exam.grade.grade'), 'grade');

            $assessment = Assessment::query()
                ->byPeriod()
                ->whereUuid($this->assessment)
                ->getOrFail(trans('exam.assessment.assessment'), 'assessment');

            $observation = $this->observation ? Observation::query()
                ->byPeriod()
                ->whereUuid($this->observation)
                ->getOrFail(trans('exam.observation.observation'), 'observation') : null;

            $primarySchedule = null;
            if (! $this->is_reassessment) {
                $existingRecords = Schedule::query()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereExamId($exam->id)
                    ->whereBatchId($batch->id)
                    ->where(function ($q) {
                        $q->where('is_reassessment', false)
                            ->orWhere('is_reassessment', null);
                    })
                    ->exists();

                if ($existingRecords) {
                    $validator->errors()->add('name', trans('global.duplicate', ['attribute' => __('exam.schedule.schedule')]));
                }
            } else {
                $attemptCount = Schedule::query()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereExamId($exam->id)
                    ->whereBatchId($batch->id)
                    ->count();

                if ($attemptCount == 0) {
                    $this->merge([
                        'is_reassessment' => false,
                    ]);
                } else {
                    $primarySchedule = Schedule::query()
                        ->whereExamId($exam->id)
                        ->whereBatchId($batch->id)
                        ->where(function ($q) {
                            $q->where('is_reassessment', false)
                                ->orWhere('is_reassessment', null);
                        })
                        ->firstOrFail();
                }

                $this->merge([
                    'attempt' => AssessmentAttempt::getAttempt($attemptCount + 1),
                ]);
            }

            $subjects = Subject::query()
                ->withSubjectRecord($batch->id, $batch->course_id)
                ->get();

            $newRecords = [];
            foreach ($this->records as $index => $record) {
                $subjectUuid = Arr::get($record, 'subject.uuid');

                $subject = $subjects->firstWhere('uuid', $subjectUuid);

                if (! $subject) {
                    throw ValidationException::withMessages(['records.'.$index.'date' => trans('global.could_not_find', ['attribute' => trans('academic.subject.subject')])]);
                }

                $hasExam = (bool) Arr::get($record, 'has_exam');

                if (! $hasExam) {
                    $newRecords[] = [
                        'subject_id' => $subject->id,
                        'has_exam' => false,
                    ];

                    continue;
                }

                $assessmentRecords = Arr::get($record, 'assessments');

                $newAssessmentRecords = [];
                foreach ($assessment->records as $assessmentIndex => $assessmentRecord) {
                    $code = Arr::get($assessmentRecord, 'code');

                    $newAssessmentRecord = Arr::first($assessmentRecords, function ($item) use ($code) {
                        return $item['code'] === $code;
                    }) ?? [];

                    if (empty($newAssessmentRecord)) {
                        throw ValidationException::withMessages(['records.'.$index.'.date' => trans('global.could_not_find', ['attribute' => trans('exam.assessment.assessment')])]);
                    }

                    $marks = Arr::get($newAssessmentRecord, 'marks');

                    $marks = explode('/', $marks);
                    $maxMark = (float) $marks[0];
                    $passingMark = $marks[1] ?? '';

                    if (! is_numeric($maxMark)) {
                        throw ValidationException::withMessages(['records.'.$index.'.assessments.'.$assessmentIndex.'.marks' => trans('validation.numeric', ['attribute' => trans('exam.schedule.props.marks')])]);
                    }

                    if ($maxMark < 0) {
                        throw ValidationException::withMessages(['records.'.$index.'.assessments.'.$assessmentIndex.'.marks' => trans('validation.gt.numeric', ['attribute' => trans('exam.schedule.props.marks'), 'value' => 0])]);
                    }

                    if (! empty($passingMark)) {
                        if (! is_numeric($passingMark)) {
                            throw ValidationException::withMessages(['records.'.$index.'.assessments.'.$assessmentIndex.'.marks' => trans('validation.numeric', ['attribute' => trans('exam.schedule.props.marks')])]);
                        }

                        if ($passingMark < 0) {
                            throw ValidationException::withMessages(['records.'.$index.'.assessments.'.$assessmentIndex.'.marks' => trans('validation.gt.numeric', ['attribute' => trans('exam.schedule.props.marks'), 'value' => 0])]);
                        }

                        if ($passingMark > $maxMark) {
                            throw ValidationException::withMessages(['records.'.$index.'.assessments.'.$assessmentIndex.'.marks' => trans('validation.lt.numeric', ['attribute' => trans('exam.schedule.props.marks'), 'value' => $maxMark])]);
                        }
                    }

                    $newAssessmentRecords[] = [
                        'code' => $newAssessmentRecord['code'],
                        'max_mark' => round($maxMark, 2),
                        'passing_mark' => ! empty($passingMark) ? round($passingMark, 2) : '',
                    ];
                }

                $newRecords[] = [
                    'subject_id' => $subject->id,
                    'has_exam' => true,
                    'assessments' => $newAssessmentRecords,
                    'date' => Arr::get($record, 'date'),
                    'start_time' => Arr::get($record, 'start_time'),
                    'duration' => Arr::get($record, 'duration'),
                ];
            }

            $additionalSubjects = collect($this->additional_subjects)->map(function ($additionalSubject) {
                return collect($additionalSubject)->only(['name', 'code', 'date', 'start_time', 'duration']);
            });

            $this->merge([
                'exam_id' => $exam->id,
                'batch_id' => $batch->id,
                'grade_id' => $grade->id,
                'assessment_id' => $assessment->id,
                'observation_id' => $observation?->id,
                'records' => $newRecords,
                'additional_subjects' => $additionalSubjects,
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
            'is_reassessment' => __('exam.schedule.reassessment'),
            'grade' => __('exam.grade.grade'),
            'assessment' => __('exam.assessment.assessment'),
            'observation' => __('exam.observation.observation'),
            'records' => __('academic.subject.subject'),
            'records.*.uuid' => __('academic.subject.subject'),
            'records.*.subject.uuid' => __('academic.subject.subject'),
            'records.*.has_exam' => __('exam.schedule.props.has_exam'),
            'records.*.assessments' => __('exam.assessment.assessment'),
            'records.*.assessments.*.code' => __('exam.assessment.props.code'),
            'records.*.assessments.*.marks' => __('exam.assessment.props.marks'),
            'records.*.assessment.uuid' => __('exam.assessment.assessment'),
            'records.*.date' => __('exam.schedule.props.date'),
            'additional_subjects' => __('academic.subject.additional_subject'),
            'additional_subjects.*.name' => __('academic.subject.props.name'),
            'additional_subjects.*.code' => __('academic.subject.props.code'),
            'additional_subjects.*.date' => __('exam.schedule.props.date'),
            'description' => __('exam.schedule.props.description'),
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
            'records.*.assessments.*.marks.required_if' => trans('validation.required', ['attribute' => trans('exam.assessment.props.marks')]),
        ];
    }
}
