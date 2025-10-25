<?php

namespace App\Services\Exam;

use App\Actions\Exam\GetAvailableSubjectForStudent;
use App\Actions\Exam\GetReassessmentSubjectForStudent;
use App\Actions\Student\CreateCustomFeeHead;
use App\Helpers\CalHelper;
use App\Models\Academic\Batch;
use App\Models\Exam\Form;
use App\Models\Exam\Schedule;
use App\Models\Finance\FeeHead;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScheduleActionService
{
    public function copyToCourse(Request $request, Schedule $schedule): void
    {
        $schedule->load('batch.course');

        if (! $schedule->batch->course->getConfig('batch_with_same_subject')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $schedule->load('records');

        $batches = Batch::query()
            ->whereCourseId($schedule->batch->course_id)
            ->where('id', '!=', $schedule->batch_id)
            ->get();

        \DB::beginTransaction();

        foreach ($batches as $batch) {
            $existingSchedule = Schedule::query()
                ->whereExamId($schedule->exam_id)
                ->whereBatchId($batch->id)
                ->whereIsReassessment($schedule->is_reassessment)
                ->whereAttempt($schedule->attempt)
                ->first();

            if ($existingSchedule) {
                $this->updateRecords($schedule, $existingSchedule);
            } else {
                $newSchedule = $schedule->replicate();
                $newSchedule->uuid = (string) Str::uuid();
                $newSchedule->batch_id = $batch->id;
                $newSchedule->details = null;
                $newSchedule->is_reassessment = $schedule->is_reassessment;
                $newSchedule->attempt = $schedule->attempt;
                $newSchedule->save();

                foreach ($schedule->records as $record) {
                    $newRecord = $record->replicate();

                    $newRecord->uuid = (string) Str::uuid();
                    $newRecord->schedule_id = $newSchedule->id;
                    $newRecord->marks = null;

                    $config['has_exam'] = (bool) Arr::get($record->config, 'has_exam');
                    $config['has_grading'] = (bool) Arr::get($record->config, 'has_grading');
                    $config['assessments'] = Arr::get($record->config, 'assessments');
                    if (! $record->subject_id) {
                        $config['subject_name'] = Arr::get($record->config, 'subject_name');
                        $config['subject_code'] = Arr::get($record->config, 'subject_code');
                    }

                    $newRecord->config = $config;
                    $newRecord->save();
                }
            }
        }

        \DB::commit();
    }

    private function updateRecords(Schedule $schedule, Schedule $existingSchedule): void
    {
        $existingSchedule->load('records');

        foreach ($schedule->records as $record) {
            $existingRecord = $existingSchedule->records->filter(function ($existingScheduleRecord) use ($record) {
                return $record->subject_id == $existingScheduleRecord->subject_id || (Arr::get($record->config, 'subject_name') && Arr::get($record->config, 'subject_name') == Arr::get($existingScheduleRecord->config, 'subject_name'));
            })->first();

            if ($existingRecord) {
                $existingRecord->date = $record->date;

                $config = $existingRecord->config;
                $config['has_exam'] = (bool) $record->getConfig('has_exam');
                $config['has_grading'] = (bool) $record->getConfig('has_grading');
                $config['assessments'] = Arr::get($record->config, 'assessments', []);

                if (! $record->subject_id) {
                    $config['subject_name'] = Arr::get($record->config, 'subject_name');
                    $config['subject_code'] = Arr::get($record->config, 'subject_code');
                }

                $existingRecord->config = $config;
                $existingRecord->save();

            } else {
                $newRecord = $record->replicate();

                $newRecord->uuid = (string) Str::uuid();
                $newRecord->schedule_id = $existingSchedule->id;
                $newRecord->marks = null;

                $config['has_exam'] = (bool) Arr::get($record->config, 'has_exam');
                $config['has_grading'] = (bool) Arr::get($record->config, 'has_grading');
                $config['assessments'] = Arr::get($record->config, 'assessments');

                if (! $record->subject_id) {
                    $config['subject_name'] = Arr::get($record->config, 'subject_name');
                    $config['subject_code'] = Arr::get($record->config, 'subject_code');
                }

                $newRecord->config = $config;

                $newRecord->save();
            }
        }
    }

    public function updateForm(Request $request, Schedule $schedule): void
    {
        if ($request->boolean('value') == (bool) $schedule->getMeta('has_form')) {
            return;
        }

        $schedule->setMeta([
            'has_form' => $request->boolean('value'),
        ]);
        $schedule->save();
    }

    public function togglePublishAdmitCard(Request $request, Schedule $schedule): void
    {
        $publishAdmitCard = $schedule->getMeta('publish_admit_card');

        if ($publishAdmitCard) {
            $schedule->setMeta(['publish_admit_card' => false]);
        } else {
            $schedule->setMeta(['publish_admit_card' => true]);
        }

        $schedule->save();
    }

    public function confirmForm(Request $request, Schedule $schedule): void
    {
        $exam = $schedule->exam;

        $lastDate = null;

        if (CalHelper::validateDate(Arr::get($exam->config, 'exam_form_last_date'))) {
            $lastDate = Arr::get($exam->config, 'exam_form_last_date');
        }

        if ($lastDate && today()->toDateString() > $lastDate) {
            throw ValidationException::withMessages(['message' => trans('exam.form.last_date_expired')]);
        }

        $studentSummary = Student::query()
            ->auth()
            ->firstOrFail();

        $student = Student::find($studentSummary->id);

        if (empty($student->fee_structure_id)) {
            throw ValidationException::withMessages(['message' => trans('student.set_fee_info')]);
        }

        $form = Form::query()
            ->whereScheduleId($schedule->id)
            ->whereStudentId($student->id)
            ->first();

        if ($form) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $reassessmentSubjects = (new GetReassessmentSubjectForStudent)->execute($student, $schedule);

        $availableSubjects = (new GetAvailableSubjectForStudent)->execute($student, $schedule);

        $payableFee = 0;

        foreach ($reassessmentSubjects as $record) {
            $payableFee += Arr::get($record, 'exam_fee')?->value ?? 0;
        }

        foreach ($availableSubjects as $record) {
            $payableFee += Arr::get($record, 'exam_fee')?->value ?? 0;
        }

        $feeHeads = FeeHead::query()
            ->byPeriod()
            ->whereIn('type', ['subject_wise_exam_fee', 'exam_form_fee', 'exam_form_late_fee'])
            ->get();

        \DB::beginTransaction();

        $form = Form::forceCreate([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'confirmed_at' => now()->toDateTimeString(),
            'meta' => [
                'reassessment_subjects' => $reassessmentSubjects,
            ],
        ]);

        $this->setExamFee($student, $feeHeads, [
            'payable_fee' => $payableFee,
            'fee_type' => 'subject_wise_exam_fee',
            'form_uuid' => $form->uuid,
        ]);

        $this->setExamFee($student, $feeHeads, [
            'payable_fee' => Arr::get($schedule->exam->config, 'exam_form_fee', 0),
            'fee_type' => 'exam_form_fee',
            'form_uuid' => $form->uuid,
        ]);

        $this->setExamFee($student, $feeHeads, [
            'payable_fee' => Arr::get($schedule->exam->config, 'exam_form_late_fee', 0),
            'fee_type' => 'exam_form_late_fee',
            'form_uuid' => $form->uuid,
        ]);

        \DB::commit();
    }

    private function setExamFee(Student $student, Collection $feeHeads, array $params = [])
    {
        $payableFee = Arr::get($params, 'payable_fee', 0);
        $feeType = Arr::get($params, 'fee_type');
        $formUuid = Arr::get($params, 'form_uuid');

        $feeHead = $feeHeads->firstWhere('type', $feeType);

        if (! $payableFee) {
            return;
        }

        if (! $feeHead) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('finance.fee.default_custom_fee_types.'.$feeType)])]);
        }

        (new CreateCustomFeeHead)->execute($student, [
            'fee_head_id' => $feeHead->id,
            'amount' => $payableFee,
            'due_date' => today()->toDateString(),
            'remarks' => '',
            'meta' => [
                'exam_form_uuid' => $formUuid,
            ],
        ]);
    }

    public function submitForm(Request $request, Schedule $schedule): void
    {
        $student = Student::query()
            ->auth()
            ->firstOrFail();

        $form = Form::query()
            ->whereScheduleId($schedule->id)
            ->whereStudentId($student->id)
            ->first();

        if (! $form) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if (! $form->confirmed_at->value) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($form->submitted_at->value) {
            throw ValidationException::withMessages(['message' => trans('exam.form.already_submitted')]);
        }

        $fees = $student->getFeeSummaryOnDate(today()->toDateString());

        $balanceFee = Arr::get($fees, 'balance_fee')?->value ?? 0;

        if ($balanceFee > 0) {
            throw ValidationException::withMessages(['message' => trans('exam.form.fee_balance', ['attribute' => Arr::get($fees, 'balance_fee')?->formatted])]);
        }

        $form->submitted_at = now()->toDateTimeString();
        $form->save();
    }
}
