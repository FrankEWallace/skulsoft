<?php

namespace App\Services\Exam;

use App\Actions\Exam\GetAvailableSubjectForStudent;
use App\Actions\Exam\GetReassessmentSubjectForStudent;
use App\Helpers\CalHelper;
use App\Http\Resources\Exam\AssessmentResource;
use App\Http\Resources\Exam\ExamResource;
use App\Http\Resources\Exam\GradeResource;
use App\Http\Resources\Exam\ObservationResource;
use App\Models\Exam\Assessment;
use App\Models\Exam\Exam;
use App\Models\Exam\Form;
use App\Models\Exam\Grade;
use App\Models\Exam\Observation;
use App\Models\Exam\Record;
use App\Models\Exam\Schedule;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function preRequisite(Request $request)
    {
        $exams = ExamResource::collection(Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->get());

        $assessments = AssessmentResource::collection(Assessment::query()
            ->byPeriod()
            ->get());

        $grades = GradeResource::collection(Grade::query()
            ->byPeriod()
            ->get());

        $observations = ObservationResource::collection(Observation::query()
            ->byPeriod()
            ->get());

        return compact('exams', 'assessments', 'grades', 'observations');
    }

    public function getFormSubmissionData(Schedule $schedule)
    {
        if (! auth()->user()->hasRole('student')) {
            return $schedule;
        }

        if (! $schedule->has_form) {
            $schedule->has_form = false;

            return $schedule;
        }

        $student = Student::query()
            ->auth()
            ->first();

        $reassessmentSubjects = (new GetReassessmentSubjectForStudent)->execute($student, $schedule);

        $availableSubjects = (new GetAvailableSubjectForStudent)->execute($student, $schedule);

        $payableFee = 0;

        foreach ($reassessmentSubjects as $record) {
            $payableFee += Arr::get($record, 'exam_fee')?->value ?? 0;
        }

        foreach ($availableSubjects as $record) {
            $payableFee += Arr::get($record, 'exam_fee')?->value ?? 0;
        }

        $examFormFee = Arr::get($schedule->exam->config, 'exam_form_fee', 0);
        $examFormLateFee = Arr::get($schedule->exam->config, 'exam_form_late_fee', 0);

        $payableFee += $examFormFee;
        $payableFee += $examFormLateFee;

        $form = Form::query()
            ->where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->first();

        $schedule->form_uuid = $form?->uuid;
        $schedule->reassessment_subjects = $reassessmentSubjects;
        $schedule->available_subjects = $availableSubjects;
        $schedule->payable_fee = $payableFee;
        $schedule->confirmed_at = $form?->confirmed_at;
        $schedule->submitted_at = $form?->submitted_at;
        $schedule->approved_at = $form?->approved_at;

        return $schedule;
    }

    public function create(Request $request): Schedule
    {
        \DB::beginTransaction();

        $schedule = Schedule::forceCreate($this->formatParams($request));

        $this->updateRecords($request, $schedule);

        $this->updateAdditionalSubjects($request, $schedule);

        \DB::commit();

        return $schedule;
    }

    private function formatParams(Request $request, ?Schedule $schedule = null): array
    {
        $formatted = [
            'exam_id' => $request->exam_id,
            'batch_id' => $request->batch_id,
            'grade_id' => $request->grade_id,
            'assessment_id' => $request->assessment_id,
            'observation_id' => $request->observation_id,
            'description' => $request->description,
        ];

        if (! $schedule) {
            $formatted['is_reassessment'] = $request->boolean('is_reassessment');
            $formatted['attempt'] = $request->attempt ?? 'first';
        }

        return $formatted;
    }

    private function updateAdditionalSubjects(Request $request, Schedule $schedule): void
    {
        $subjectNames = [];
        foreach ($request->additional_subjects as $subject) {
            $subjectNames[] = Arr::get($subject, 'name');

            $examRecord = Record::query()
                ->whereScheduleId($schedule->id)
                ->where('config->subject_name', Arr::get($subject, 'name'))
                ->first();

            if (! $examRecord) {
                $date = Arr::get($subject, 'date') ?: null;
                $startTime = null;
                if (! empty($date) && Arr::get($subject, 'start_time')) {
                    $startTime = CalHelper::storeDateTime($date.' '.Arr::get($subject, 'start_time'))?->toTimeString();
                }

                $examRecord = Record::forceCreate([
                    'schedule_id' => $schedule->id,
                    'config' => [
                        'subject_name' => Arr::get($subject, 'name'),
                        'subject_code' => Arr::get($subject, 'code'),
                    ],
                    'date' => $date,
                    'start_time' => $startTime,
                    'duration' => Arr::get($subject, 'duration') ?: null,
                ]);
            } else {
                $date = Arr::get($subject, 'date') ?: null;
                $startTime = null;
                if (! empty($date) && Arr::get($subject, 'start_time')) {
                    $startTime = CalHelper::storeDateTime($date.' '.Arr::get($subject, 'start_time'))?->toTimeString();
                }

                $config = $examRecord->config;
                $config['subject_code'] = Arr::get($subject, 'code');
                $examRecord->config = $config;

                $examRecord->date = $date;
                $examRecord->start_time = $startTime;
                $examRecord->duration = Arr::get($subject, 'duration') ?: null;
                $examRecord->save();
            }
        }

        Record::query()
            ->whereScheduleId($schedule->id)
            ->whereNull('subject_id')
            ->whereNotIn('config->subject_name', $subjectNames)
            ->delete();
    }

    private function updateRecords(Request $request, Schedule $schedule): void
    {
        $subjectIds = [];
        foreach ($request->records as $record) {
            $subjectIds[] = Arr::get($record, 'subject_id');

            $examRecord = Record::firstOrCreate([
                'schedule_id' => $schedule->id,
                'subject_id' => Arr::get($record, 'subject_id'),
            ]);

            $config = $examRecord->config ?? [];
            $hasExam = (bool) Arr::get($record, 'has_exam');

            if (! empty($record->marks)) {

                if ($hasExam) {
                    $date = Arr::get($record, 'date') ?: null;
                    $startTime = null;
                    if (! empty($date) && Arr::get($record, 'start_time')) {
                        $startTime = CalHelper::storeDateTime($date.' '.Arr::get($record, 'start_time'))?->toTimeString();
                    }

                    $examRecord->date = $date;
                    $examRecord->startTime = $startTime;
                    $examRecord->duration = Arr::get($record, 'duration') ?: null;
                    $examRecord->config = $config;
                    $examRecord->save();
                }

                continue;
            }

            if (! $hasExam) {
                $examRecord->date = null;
                $examRecord->start_time = null;
                $examRecord->duration = null;
                $config['has_exam'] = false;
                $config['assessments'] = [];
            } else {
                $date = Arr::get($record, 'date') ?: null;

                $startTime = null;
                if (! empty($date) && Arr::get($record, 'start_time')) {
                    $startTime = CalHelper::storeDateTime($date.' '.Arr::get($record, 'start_time'))?->toTimeString();
                }

                $examRecord->date = $date;
                $examRecord->start_time = $startTime;
                $examRecord->duration = Arr::get($record, 'duration') ?: null;
                $config['has_exam'] = true;
                $config['assessments'] = Arr::get($record, 'assessments', []);
            }

            $examRecord->config = $config;
            $examRecord->save();
        }

        Record::query()
            ->whereScheduleId($schedule->id)
            ->whereNotNull('subject_id')
            ->whereNotIn('subject_id', $subjectIds)
            ->delete();
    }

    public function update(Request $request, Schedule $schedule): void
    {
        \DB::beginTransaction();

        $schedule->forceFill($this->formatParams($request, $schedule))->save();

        $this->updateRecords($request, $schedule);

        $this->updateAdditionalSubjects($request, $schedule);

        \DB::commit();
    }

    public function deletable(Schedule $schedule): bool
    {
        $examRecordExists = \DB::table('exam_records')
            ->whereScheduleId($schedule->id)
            ->whereNotNull('marks')
            ->exists();

        if ($examRecordExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('exam.schedule.schedule'), 'dependency' => trans('exam.assessment.props.mark')])]);
        }

        return true;
    }

    public function delete(Schedule $schedule)
    {
        $schedule->delete();
    }
}
