<?php

namespace App\Services\Exam;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Enums\Exam\AssessmentAttempt;
use App\Enums\Exam\Result;
use App\Http\Resources\Exam\ExamResource;
use App\Http\Resources\Student\StudentResource;
use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Academic\TimetableAllocation;
use App\Models\Employee\Employee;
use App\Models\Exam\Exam;
use App\Models\Exam\Record;
use App\Models\Exam\Result as ExamResult;
use App\Models\Exam\Schedule;
use App\Models\Incharge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class MarkService
{
    public function preRequisite(Request $request)
    {
        $exams = ExamResource::collection(Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->get());

        $attempts = AssessmentAttempt::getOptions();

        return compact('exams', 'attempts');
    }

    public function validateInput(Request $request): array
    {
        $request->validate([
            'exam' => 'required|uuid',
            'batch' => 'required|uuid',
            'subject' => 'required|uuid',
            'attempt' => ['required', new Enum(AssessmentAttempt::class)],
        ]);

        $exam = Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->whereUuid($request->exam)
            ->getOrFail(trans('exam.exam'), 'exam');

        $batch = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'batch');

        $subject = Subject::query()
            ->findByBatchOrFail($batch->id, $batch->course_id, $request->subject);

        if (auth()->user()->can('exam:marks-record')) {
        } elseif (auth()->user()->can('exam:subject-incharge-wise-marks-record')) {
            $employee = Employee::auth()->first();

            $incharges = Incharge::query()
                ->whereModelType('Subject')
                ->whereModelId($subject->id)
                ->whereDetailType('Batch')
                ->whereDetailId($batch->id)
                ->get()
                ->pluck('employee_id')
                ->all();

            $timetableTeachers = TimetableAllocation::query()
                ->select('timetable_allocations.employee_id')
                ->join('timetable_records', 'timetable_allocations.timetable_record_id', '=', 'timetable_records.id')
                ->join('timetables', 'timetable_records.timetable_id', '=', 'timetables.id')
                ->where('timetable_allocations.subject_id', $subject->id)
                ->where('timetables.batch_id', $batch->id)
                ->get();

            $allowedEmployeeIds = array_merge($incharges, $timetableTeachers->pluck('employee_id')->all());

            if (! in_array($employee->id, $allowedEmployeeIds)) {
                throw ValidationException::withMessages(['message' => trans('exam.marks_record_permission_denied')]);
            }
        }

        $schedule = Schedule::query()
            ->whereExamId($exam->id)
            ->whereBatchId($batch->id)
            ->where('attempt', $request->attempt)
            ->getOrFail(trans('exam.schedule.schedule'));

        $examRecord = Record::query()
            ->whereScheduleId($schedule->id)
            ->whereSubjectId($subject->id)
            ->first();

        if (! $examRecord) {
            throw ValidationException::withMessages(['message' => trans('exam.schedule.could_not_find_record')]);
        }

        if (! $examRecord->getConfig('has_exam')) {
            throw ValidationException::withMessages(['message' => trans('exam.schedule.record_has_no_exam')]);
        }

        $previousSchedule = null;
        $previousAttempt = null;
        $previousExamRecord = null;
        if ($request->attempt != AssessmentAttempt::FIRST->value) {
            $attemptNumber = AssessmentAttempt::getAttemptNumber($schedule->attempt->value);
            $previousAttempt = AssessmentAttempt::getAttempt($attemptNumber - 1);

            $previousSchedule = Schedule::query()
                ->whereExamId($exam->id)
                ->whereBatchId($batch->id)
                ->where('attempt', $previousAttempt)
                ->getOrFail(trans('exam.schedule.schedule'));

            $previousExamRecord = Record::query()
                ->whereScheduleId($schedule->id)
                ->whereSubjectId($subject->id)
                ->getOrFail(trans('academic.subject.subject'));
        }

        $schedule->load('assessment');

        $request->merge([
            'select_all' => true,
        ]);

        $params = $request->all();
        $params['for_subject'] = true;

        if ($request->boolean('show_all_student')) {
            $params['status'] = 'all';
        }

        $students = (new FetchBatchWiseStudent)->execute($params);

        if ($request->attempt != AssessmentAttempt::FIRST->value) {
            $examResults = ExamResult::query()
                ->whereExamId($exam->id)
                ->whereIn('student_id', $students->pluck('id')->all())
                ->get();

            $filteredStudentIds = [];
            foreach ($students as $student) {
                $reassessmentSubjectCodes = [];

                $examResult = $examResults->firstWhere('student_id', $student->id);

                if (! $examResult) {
                    continue;
                }

                if ($examResult->result == Result::REASSESSMENT) {
                    $reassessmentSubjectCodes = Arr::get($examResult->subjects, 'reassessment', []);
                }

                if (in_array($subject->code, $reassessmentSubjectCodes)) {
                    $filteredStudentIds[] = $student->id;
                }
            }

            $students = collect($students)->filter(function ($student) use ($filteredStudentIds) {
                return in_array($student->id, $filteredStudentIds);
            })->values();
        }

        return [
            'exam' => $exam,
            'batch' => $batch,
            'subject' => $subject,
            'schedule' => $schedule,
            'exam_record' => $examRecord,
            'students' => $students,
            'previous_schedule' => $previousSchedule,
            'previous_exam_record' => $previousExamRecord,
        ];
    }

    public function fetch(Request $request)
    {
        $data = $this->validateInput($request);

        $exam = $data['exam'];
        $batch = $data['batch'];
        $subject = $data['subject'];
        $schedule = $data['schedule'];
        $examRecord = $data['exam_record'];
        $students = $data['students'];

        $scheduleAssessment = collect($schedule->assessment->records ?? []);

        $recordMarks = $examRecord->marks ?? [];

        $assessments = collect($examRecord->getConfig('assessments', []))
            ->filter(function ($assessment) {
                return Arr::get($assessment, 'max_mark', 0) > 0;
            })
            ->map(function ($assessment) use ($scheduleAssessment) {
                $code = Arr::get($assessment, 'code');

                return [
                    'name' => Arr::get($scheduleAssessment->firstWhere('code', $code), 'name'),
                    'code' => $code,
                    'max_mark' => Arr::get($assessment, 'max_mark'),
                ];
            });

        $notApplicableStudents = $examRecord->getConfig('not_applicable_students', []);

        $comments = collect($examRecord->getConfig('comments') ?? []);
        foreach ($students as $student) {
            $comment = Arr::get($comments->firstWhere('uuid', $student->uuid), 'comment');

            $marks = [];
            foreach ($assessments as $assessment) {
                $assessmentCode = Arr::get($assessment, 'code');

                $assessmentMark = collect($recordMarks)->firstWhere('code', $assessmentCode);

                $studentMarks = $assessmentMark['marks'] ?? [];

                $studentMark = collect($studentMarks)->firstWhere('uuid', $student->uuid);

                $marks[] = [
                    'code' => $assessmentCode,
                    'name' => Arr::get($assessment, 'name'),
                    'max_mark' => Arr::get($assessment, 'max_mark'),
                    'obtained_mark' => $studentMark['obtained_mark'] ?? '',
                ];
            }

            $student->marks = $marks;
            $student->comment = $comment;
            $student->is_not_applicable = in_array($student->uuid, $notApplicableStudents) ? true : false;
            $student->has_exam_mark = true;
        }

        $autoLockDate = null;
        $isLocked = false;
        if (config('config.exam.enable_auto_lock_marks')) {
            $autoLockMarksPeriod = config('config.exam.auto_lock_marks_period', 7);

            $examDate = Carbon::parse($examRecord->date->value);
            $autoLockDate = $examDate->addDays((int) $autoLockMarksPeriod);

            $isLocked = $autoLockDate->isPast();

            $autoLockDate = $autoLockDate->toDateString();
        }

        return StudentResource::collection($students)
            ->additional([
                'meta' => [
                    'assessments' => $assessments,
                    'mark_recorded' => (bool) Arr::get($examRecord->config, 'mark_recorded'),
                    'marksheet_status' => $schedule->getConfig('marksheet_status'),
                    'auto_lock_date' => \Cal::date($autoLockDate)?->formatted,
                    'is_locked' => $isLocked,
                ],
            ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateInput($request);

        $exam = $data['exam'];
        $batch = $data['batch'];
        $subject = $data['subject'];
        $schedule = $data['schedule'];
        $examRecord = $data['exam_record'];
        $students = $data['students'];

        if (! auth()->user()->hasRole('admin') && config('config.exam.enable_auto_lock_marks')) {
            $autoLockMarksPeriod = config('config.exam.auto_lock_marks_period', 7);

            $examDate = Carbon::parse($examRecord->date->value);
            $autoLockDate = $examDate->addDays((int) $autoLockMarksPeriod);

            if ($autoLockDate->isPast()) {
                throw ValidationException::withMessages(['message' => trans('exam.schedule.could_not_record_mark_for_expired_period')]);
            }
        }

        if (array_diff(Arr::pluck($request->students, 'uuid'), Arr::pluck($students, 'uuid'))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        \DB::beginTransaction();

        $marks = $request->marks;
        $notApplicableStudents = $request->not_applicable_students ?? [];
        // foreach ($request->students as $input) {
        //     $student = collect($students)->where('uuid', Arr::get($input, 'uuid'))->first();
        // }

        $examRecord->marks = $marks;
        $examRecord->setConfig([
            'ranking' => $request->ranking,
            'comments' => $request->comments,
            'mark_recorded' => true,
            'not_applicable_students' => $notApplicableStudents,
        ]);
        $examRecord->save();

        $schedule->setConfig([
            'marksheet_status' => 'pending',
        ]);
        $schedule->save();

        \DB::commit();
    }

    public function remove(Request $request)
    {
        $data = $this->validateInput($request);

        $examRecord = $data['exam_record'];
        $schedule = $data['schedule'];

        \DB::beginTransaction();

        $examRecord->marks = [];
        $examRecord->setConfig([
            'comments' => [],
            'mark_recorded' => false,
            'not_applicable_students' => [],
        ]);
        $examRecord->save();

        $schedule->setConfig([
            'marksheet_status' => 'pending',
        ]);
        $schedule->save();

        \DB::commit();
    }
}
