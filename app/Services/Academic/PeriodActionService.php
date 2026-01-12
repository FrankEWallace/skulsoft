<?php

namespace App\Services\Academic;

use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\ClassTiming;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Division;
use App\Domain\Academic\Models\Period;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\SubjectRecord;
use App\Models\Exam\Assessment;
use App\Models\Exam\Exam;
use App\Models\Exam\Grade;
use App\Models\Exam\Observation;
use App\Models\Exam\Term;
use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeHead;
use App\Models\Transport\Circle;
use App\Models\Transport\Fee;
use App\Models\Transport\Stoppage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PeriodActionService
{
    public function select(Request $request, Period $period): void
    {
        $user = \Auth::user();

        if ($user->getPreference('academic.period_id') && $user->current_period_id == $period->id) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if (! in_array($period->id, config('config.academic.periods', []))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $selectionHistory = $user->updateSelectionHistory([
            'team_id' => $user->current_team_id,
            'period_id' => $period->id,
        ]);

        $preference = $user->preference;
        $preference['academic']['period_id'] = $period->id;
        $user->setMeta([
            'selection_history' => $selectionHistory,
        ]);
        $user->preference = $preference;
        $user->save();
    }

    public function default(Request $request, Period $period): void
    {
        if ($period->is_default) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        Period::query()
            ->byTeam()
            ->update(['is_default' => false]);

        $period->update(['is_default' => true]);
    }

    public function import(Request $request, Period $period): void
    {
        $this->replicateDivision($request, $period);

        $this->replicateCourse($request, $period);

        $this->replicateBatch($request, $period);

        $this->replicateSubject($request, $period);

        $this->replicateSubjectAllocation($request, $period);

        $this->replicateFeeGroup($request, $period);

        $this->replicateFeeHead($request, $period);

        $this->replicateFeeConcession($request, $period);

        $this->replicateTransportCircle($request, $period);

        $this->replicateTransportFee($request, $period);

        $this->replicateTransportStoppage($request, $period);

        $this->replicateExamTerm($request, $period);

        $this->replicateExam($request, $period);

        $this->replicateExamGrade($request, $period);

        $this->replicateExamAssessment($request, $period);

        $this->replicateExamObservation($request, $period);

        $this->replicateClassTiming($request, $period);
    }

    private function replicateDivision(Request $request, Period $period): void
    {
        if (! $request->boolean('division') && ! $request->boolean('course') && ! $request->boolean('batch') && ! $request->boolean('subject')) {
            return;
        }

        $divisions = Division::query()
            ->where('period_id', $request->period_id)
            ->get();

        \DB::beginTransaction();
        foreach ($divisions as $division) {
            Division::query()
                ->where('period_id', $period->id)
                ->where('name', $division->name)
                ->where('program_id', $division->program_id)
                ->firstOr(function () use ($division, $period) {
                    $newDivision = $division->replicate();
                    $newDivision->uuid = (string) Str::uuid();
                    $newDivision->period_id = $period->id;
                    $newDivision->program_id = $division->program_id;
                    $newDivision->period_start_date = null;
                    $newDivision->period_end_date = null;
                    $newDivision->position = $division->position;
                    $newDivision->save();
                });
        }
        \DB::commit();
    }

    private function replicateCourse(Request $request, Period $period)
    {
        if (! $request->boolean('course') && ! $request->boolean('batch') && ! $request->boolean('subject')) {
            return;
        }

        $courses = Course::query()
            ->whereHas('division', function ($q) use ($request) {
                $q->where('period_id', $request->period_id);
            })
            ->get();

        $newDivisions = Division::query()
            ->where('period_id', $period->id)
            ->get();

        \DB::beginTransaction();
        foreach ($courses as $course) {
            Course::query()
                ->whereHas('division', function ($q) use ($period, $course) {
                    $q->where('period_id', $period->id)
                        ->where('name', $course->division->name);
                })
                ->where('name', $course->name)
                ->firstOr(function () use ($course, $newDivisions) {
                    $division = $newDivisions
                        ->where('name', $course->division->name)
                        ->where('program_id', $course->division->program_id)
                        ->first();

                    if (! $division) {
                        throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.division.division')])]);
                    }

                    $newCourse = $course->replicate();
                    $newCourse->uuid = (string) Str::uuid();
                    $newCourse->division_id = $division->id;
                    $newCourse->period_start_date = null;
                    $newCourse->period_end_date = null;
                    $newCourse->position = $course->position;
                    $newCourse->save();
                });
        }
        \DB::commit();
    }

    private function replicateBatch(Request $request, Period $period)
    {
        if (! $request->boolean('batch') && ! $request->boolean('subject')) {
            return;
        }

        $batches = Batch::query()
            ->whereHas('course', function ($q) use ($request) {
                $q->whereHas('division', function ($q) use ($request) {
                    $q->where('period_id', $request->period_id);
                });
            })
            ->get();

        $newCourses = Course::query()
            ->whereHas('division', function ($q) use ($period) {
                $q->where('period_id', $period->id);
            })
            ->get();

        \DB::beginTransaction();
        foreach ($batches as $batch) {
            Batch::query()
                ->whereHas('course', function ($q) use ($period, $batch) {
                    $q->whereHas('division', function ($q) use ($period) {
                        $q->where('period_id', $period->id);
                    })
                        ->where('name', $batch->course->name);
                })
                ->where('name', $batch->name)
                ->firstOr(function () use ($batch, $newCourses) {
                    $course = $newCourses->where('name', $batch->course->name)->first();

                    if (! $course) {
                        throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.course.course')])]);
                    }

                    $newBatch = $batch->replicate();
                    $newBatch->uuid = (string) Str::uuid();
                    $newBatch->course_id = $course->id;
                    $newBatch->period_start_date = null;
                    $newBatch->period_end_date = null;
                    $newBatch->position = $batch->position;
                    $newBatch->save();
                });
        }
        \DB::commit();
    }

    private function replicateSubject(Request $request, Period $period)
    {
        if (! $request->boolean('subject')) {
            return;
        }

        $subjects = Subject::query()
            ->where('period_id', $request->period_id)
            ->get();

        \DB::beginTransaction();
        foreach ($subjects as $subject) {
            Subject::query()
                ->where('period_id', $period->id)
                ->where('name', $subject->name)
                ->firstOr(function () use ($subject, $period) {
                    $newSubject = $subject->replicate();
                    $newSubject->uuid = (string) Str::uuid();
                    $newSubject->period_id = $period->id;
                    $newSubject->position = $subject->position;
                    $newSubject->save();
                });
        }
        \DB::commit();
    }

    private function replicateSubjectAllocation(Request $request, Period $period)
    {
        if (! $request->boolean('subject_allocation')) {
            return;
        }

        $subjectRecords = SubjectRecord::query()
            ->with('course', 'batch.course')
            ->whereHas('subject', function ($q) use ($request) {
                $q->where('period_id', $request->period_id);
            })
            ->get();

        $courses = Course::query()
            ->whereHas('division', function ($q) use ($period) {
                $q->where('period_id', $period->id);
            })
            ->get();

        $batches = Batch::query()
            ->with('course')
            ->whereIn('course_id', $courses->pluck('id'))
            ->get();

        $subjects = Subject::query()
            ->where('period_id', $period->id)
            ->get();

        \DB::beginTransaction();
        foreach ($subjectRecords as $subjectRecord) {
            $subject = $subjects->where('name', $subjectRecord->subject->name)->first();

            if (! $subject) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.subject.subject')])]);
            }

            $course = null;
            $batch = null;
            if ($subjectRecord->course_id) {
                $course = $courses->where('name', $subjectRecord->course->name)->first();

                if (! $course) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.course.course')])]);
                }
            } elseif ($subjectRecord->batch_id) {
                $batch = $batches
                    ->where('course.name', $subjectRecord->batch->course->name)
                    ->where('name', $subjectRecord->batch->name)
                    ->first();

                if (! $batch) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.batch.batch')])]);
                }
            }

            $newSubjectRecord = $subjectRecord->replicate();
            $newSubjectRecord->uuid = (string) Str::uuid();
            $newSubjectRecord->subject_id = $subject->id;
            $newSubjectRecord->course_id = $course?->id;
            $newSubjectRecord->batch_id = $batch?->id;
            $newSubjectRecord->save();
        }
        \DB::commit();
    }

    private function replicateFeeGroup(Request $request, Period $period)
    {
        if (! $request->boolean('fee_group') && ! $request->boolean('fee_head') && ! $request->boolean('fee_concession')) {
            return;
        }

        $feeGroups = FeeGroup::query()
            ->where('period_id', $request->period_id)
            ->get();

        \DB::beginTransaction();
        foreach ($feeGroups as $feeGroup) {
            FeeGroup::query()
                ->where('period_id', $period->id)
                ->where('name', $feeGroup->name)
                ->firstOr(function () use ($feeGroup, $period) {
                    $newFeeGroup = $feeGroup->replicate();
                    $newFeeGroup->uuid = (string) Str::uuid();
                    $newFeeGroup->period_id = $period->id;
                    $newFeeGroup->position = $feeGroup->position;
                    $newFeeGroup->save();
                });
        }
        \DB::commit();
    }

    private function replicateFeeHead(Request $request, Period $period)
    {
        if (! $request->boolean('fee_head') && ! $request->boolean('fee_concession')) {
            return;
        }

        $feeHeads = FeeHead::query()
            ->whereHas('group', function ($q) use ($request) {
                $q->where('period_id', $request->period_id)
                    ->where(function ($q) {
                        $q->where('meta->is_custom', false)
                            ->orWhereNull('meta->is_custom');
                    });
            })
            ->get();

        $newFeeGroups = FeeGroup::query()
            ->where('period_id', $period->id)
            ->get();

        \DB::beginTransaction();
        foreach ($feeHeads as $feeHead) {
            FeeHead::query()
                ->whereHas('group', function ($q) use ($period, $feeHead) {
                    $q->where('period_id', $period->id)
                        ->where('name', $feeHead->group->name);
                })
                ->where('name', $feeHead->name)
                ->firstOr(function () use ($feeHead, $newFeeGroups, $period) {
                    $feeGroup = $newFeeGroups->where('name', $feeHead->group->name)->first();

                    if (! $feeGroup) {
                        throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('finance.fee_group.fee_group')])]);
                    }

                    $newFeeHead = $feeHead->replicate();
                    $newFeeHead->uuid = (string) Str::uuid();
                    $newFeeHead->fee_group_id = $feeGroup->id;
                    $newFeeHead->period_id = $period->id;
                    $newFeeHead->position = $feeHead->position;
                    $newFeeHead->save();
                });
        }
        \DB::commit();
    }

    private function replicateFeeConcession(Request $request, Period $period)
    {
        if (! $request->boolean('fee_concession')) {
            return;
        }

        $feeConcessions = FeeConcession::query()
            ->with('records.head')
            ->where('period_id', $request->period_id)
            ->get();

        $feeHeads = FeeHead::query()
            ->whereHas('group', function ($q) use ($period) {
                $q->where('period_id', $period->id);
            })
            ->get();

        \DB::beginTransaction();
        foreach ($feeConcessions as $feeConcession) {
            FeeConcession::query()
                ->where('period_id', $period->id)
                ->where('name', $feeConcession->name)
                ->firstOr(function () use ($feeConcession, $period, $feeHeads) {
                    $newFeeConcession = $feeConcession->replicate();
                    $newFeeConcession->uuid = (string) Str::uuid();
                    $newFeeConcession->period_id = $period->id;
                    $newFeeConcession->save();

                    foreach ($feeConcession->records as $record) {
                        $feeHead = $feeHeads->where('name', $record->head->name)->first();

                        $newFeeConcessionRecord = $record->replicate();
                        $newFeeConcessionRecord->uuid = (string) Str::uuid();
                        $newFeeConcessionRecord->fee_concession_id = $newFeeConcession->id;
                        $newFeeConcessionRecord->fee_head_id = $feeHead->id;
                        $newFeeConcessionRecord->save();
                    }
                });
        }
        \DB::commit();
    }

    private function replicateTransportCircle(Request $request, Period $period)
    {
        if (! $request->boolean('transport_circle') && ! $request->boolean('transport_fee')) {
            return;
        }

        $transportCircles = Circle::query()
            ->where('period_id', $request->period_id)
            ->get();

        \DB::beginTransaction();
        foreach ($transportCircles as $transportCircle) {
            Circle::query()
                ->where('period_id', $period->id)
                ->where('name', $transportCircle->name)
                ->firstOr(function () use ($transportCircle, $period) {
                    $newTransportCircle = $transportCircle->replicate();
                    $newTransportCircle->uuid = (string) Str::uuid();
                    $newTransportCircle->period_id = $period->id;
                    $newTransportCircle->position = $transportCircle->position;
                    $newTransportCircle->save();
                });
        }
        \DB::commit();
    }

    private function replicateTransportFee(Request $request, Period $period)
    {
        if (! $request->boolean('transport_fee')) {
            return;
        }

        $transportFees = Fee::query()
            ->with('records.circle')
            ->where('period_id', $request->period_id)
            ->get();

        $transportCircles = Circle::query()
            ->where('period_id', $period->id)
            ->get();

        \DB::beginTransaction();
        foreach ($transportFees as $transportFee) {
            Fee::query()
                ->where('period_id', $period->id)
                ->where('name', $transportFee->name)
                ->firstOr(function () use ($transportFee, $period, $transportCircles) {
                    $newTransportFee = $transportFee->replicate();
                    $newTransportFee->uuid = (string) Str::uuid();
                    $newTransportFee->period_id = $period->id;
                    $newTransportFee->save();

                    foreach ($transportFee->records as $record) {
                        $transportCircle = $transportCircles->where('name', $record->circle->name)->first();

                        $newTransportFeeRecord = $record->replicate();
                        $newTransportFeeRecord->uuid = (string) Str::uuid();
                        $newTransportFeeRecord->transport_fee_id = $newTransportFee->id;
                        $newTransportFeeRecord->transport_circle_id = $transportCircle->id;
                        $newTransportFeeRecord->save();
                    }
                });
        }
        \DB::commit();
    }

    private function replicateTransportStoppage(Request $request, Period $period)
    {
        $transportStoppages = Stoppage::query()
            ->where('period_id', $request->period_id)
            ->get();

        \DB::beginTransaction();
        foreach ($transportStoppages as $transportStoppage) {
            Stoppage::query()
                ->where('period_id', $period->id)
                ->where('name', $transportStoppage->name)
                ->firstOr(function () use ($transportStoppage, $period) {
                    $newTransportStoppage = $transportStoppage->replicate();
                    $newTransportStoppage->uuid = (string) Str::uuid();
                    $newTransportStoppage->period_id = $period->id;
                    $newTransportStoppage->save();
                });
        }
        \DB::commit();
    }

    private function replicateExamTerm(Request $request, Period $period)
    {
        if (! $request->boolean('exam_term')) {
            return;
        }

        $examTerms = Term::query()
            ->where('period_id', $request->period_id)
            ->with('division')
            ->get();

        $divisions = Division::query()
            ->where('period_id', $period->id)
            ->get();

        \DB::beginTransaction();
        foreach ($examTerms as $examTerm) {
            $newDivision = null;
            if ($examTerm->division_id) {
                $division = $examTerm->division;

                $newDivision = $divisions->where('name', $division->name)->first();

                if (! $newDivision) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.division.division')])]);
                }
            }

            Term::query()
                ->where('period_id', $period->id)
                ->where('name', $examTerm->name)
                ->where('division_id', $newDivision?->id)
                ->firstOr(function () use ($examTerm, $newDivision, $period) {
                    $newExamTerm = $examTerm->replicate();
                    $newExamTerm->uuid = (string) Str::uuid();
                    $newExamTerm->period_id = $period->id;
                    $newExamTerm->division_id = $newDivision?->id;
                    $newExamTerm->config = [];
                    $newExamTerm->meta = [];
                    $newExamTerm->save();
                });
        }
        \DB::commit();
    }

    private function replicateExam(Request $request, Period $period)
    {
        if (! $request->boolean('exam')) {
            return;
        }

        $exams = Exam::query()
            ->with('term.division')
            ->where('period_id', $request->period_id)
            ->get();

        $newExamTerms = Term::query()
            ->with('division')
            ->where('period_id', $period->id)
            ->get();

        \DB::beginTransaction();
        foreach ($exams as $exam) {
            $newExamTerm = null;
            if ($exam->term_id) {
                $examTerm = $exam->term->name;

                $newExamTerm = $newExamTerms->where('name', $examTerm)->where('division.name', $exam->term?->division?->name)->first();

                if (! $newExamTerm) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('exam.term.term')])]);
                }
            }

            Exam::query()
                ->where('period_id', $period->id)
                ->where('name', $exam->name)
                ->where('term_id', $newExamTerm?->id)
                ->firstOr(function () use ($exam, $newExamTerm, $period) {
                    $newExam = $exam->replicate();
                    $newExam->uuid = (string) Str::uuid();
                    $newExam->term_id = $newExamTerm?->id;
                    $newExam->period_id = $period->id;
                    $newExam->position = $exam->position;
                    $newExam->config = [];
                    $newExam->meta = [];
                    $newExam->save();
                });
        }
        \DB::commit();
    }

    private function replicateExamGrade(Request $request, Period $period)
    {
        if (! $request->boolean('exam_grade')) {
            return;
        }

        $examGrades = Grade::query()
            ->where('period_id', $request->period_id)
            ->get();

        \DB::beginTransaction();
        foreach ($examGrades as $examGrade) {
            Grade::query()
                ->where('period_id', $period->id)
                ->where('name', $examGrade->name)
                ->firstOr(function () use ($examGrade, $period) {
                    $newExamGrade = $examGrade->replicate();
                    $newExamGrade->uuid = (string) Str::uuid();
                    $newExamGrade->period_id = $period->id;
                    $newExamGrade->save();
                });
        }
        \DB::commit();
    }

    private function replicateExamAssessment(Request $request, Period $period)
    {
        if (! $request->boolean('exam_assessment')) {
            return;
        }

        $examAssessments = Assessment::query()
            ->where('period_id', $request->period_id)
            ->get();

        \DB::beginTransaction();
        foreach ($examAssessments as $examAssessment) {
            Assessment::query()
                ->where('period_id', $period->id)
                ->where('name', $examAssessment->name)
                ->firstOr(function () use ($examAssessment, $period) {
                    $newExamAssessment = $examAssessment->replicate();
                    $newExamAssessment->uuid = (string) Str::uuid();
                    $newExamAssessment->period_id = $period->id;
                    $newExamAssessment->save();
                });
        }
        \DB::commit();
    }

    private function replicateExamObservation(Request $request, Period $period)
    {
        if (! $request->boolean('exam_observation')) {
            return;
        }

        $examObservations = Observation::query()
            ->where('period_id', $request->period_id)
            ->get();

        $newExamGrades = Grade::query()
            ->where('period_id', $period->id)
            ->get();

        \DB::beginTransaction();
        foreach ($examObservations as $examObservation) {
            $newExamGrade = null;
            if ($examObservation->grade_id) {
                $newExamGrade = $newExamGrades->where('name', $examObservation->grade->name)->first();

                if (! $newExamGrade) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('exam.grade.grade')])]);
                }
            }

            Assessment::query()
                ->where('period_id', $period->id)
                ->where('name', $examObservation->name)
                ->firstOr(function () use ($examObservation, $newExamGrade, $period) {
                    $newExamObservation = $examObservation->replicate();
                    $newExamObservation->uuid = (string) Str::uuid();
                    $newExamObservation->period_id = $period->id;
                    $newExamObservation->grade_id = $newExamGrade?->id;
                    $newExamObservation->save();
                });
        }
        \DB::commit();
    }

    private function replicateClassTiming(Request $request, Period $period)
    {
        if (! $request->boolean('class_timing')) {
            return;
        }

        $classTimings = ClassTiming::query()
            ->where('period_id', $request->period_id)
            ->get();

        \DB::beginTransaction();
        foreach ($classTimings as $classTiming) {
            $newClassTiming = ClassTiming::query()
                ->where('period_id', $period->id)
                ->where('name', $classTiming->name)
                ->firstOr(function () use ($classTiming, $period) {
                    $newClassTiming = $classTiming->replicate();
                    $newClassTiming->uuid = (string) Str::uuid();
                    $newClassTiming->period_id = $period->id;
                    $newClassTiming->records = [];
                    $newClassTiming->config = [];
                    $newClassTiming->meta = [];
                    $newClassTiming->save();

                    return $newClassTiming;
                });

            foreach ($classTiming->sessions as $session) {
                $newClassTimingSession = $session->replicate();
                $newClassTimingSession->uuid = (string) Str::uuid();
                $newClassTimingSession->class_timing_id = $newClassTiming->id;
                $newClassTimingSession->save();
            }
        }
        \DB::commit();
    }
}
