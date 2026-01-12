<?php

namespace App\Actions\Exam;

use App\Enums\Exam\AssessmentAttempt;
use App\Enums\Exam\ReportType;
use App\Enums\Exam\Result;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Subject;
use App\Models\Exam\Exam;
use App\Models\Exam\Result as ExamResult;
use App\Models\Exam\Schedule;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;
use App\Support\HasGrade;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProcessCreditBasedMarksheet
{
    use HasGrade;

    public function execute(Batch $batch, Collection $students, array $params)
    {
        $params['subject_absent_criteria'] = 'all'; // all | any

        $allStudents = $students;

        $exam = Exam::query()
            ->byPeriod()
            ->where('uuid', Arr::get($params, 'exam'))
            ->firstOrFail();

        $schedule = Schedule::query()
            ->with('records', 'assessment', 'grade')
            ->whereExamId($exam->id)
            ->whereBatchId($batch->id)
            ->where(function ($q) {
                $q->where('is_reassessment', false)
                    ->orWhere('is_reassessment', null);
            })
            ->where('attempt', AssessmentAttempt::FIRST->value)
            ->firstOrFail();

        $inputSchedule = $schedule;

        $reassessmentSchedules = null;
        $reassessmentAttempts = [];
        if (Arr::get($params, 'attempt') != AssessmentAttempt::FIRST->value) {
            $reassessmentNumber = AssessmentAttempt::getAttemptNumber(Arr::get($params, 'attempt'));

            $previousReassessmentNumber = $reassessmentNumber - 1;

            $previousAttempt = AssessmentAttempt::getAttempt($previousReassessmentNumber);

            $previousExamResults = ExamResult::query()
                ->whereExamId($exam->id)
                ->whereAttempt($previousAttempt)
                ->whereIn('student_id', $students->pluck('id')->all())
                ->get();

            $reassessmentResults = $previousExamResults->where('result.value', Result::REASSESSMENT->value);

            $students = $students->filter(function ($student) use ($reassessmentResults) {
                return $reassessmentResults->contains('student_id', $student->id);
            });

            for ($i = $reassessmentNumber; $i > 1; $i--) {
                $reassessmentAttempts[] = [
                    'number' => $i,
                    'value' => AssessmentAttempt::getAttempt($i),
                ];
            }

            $reassessmentSchedules = Schedule::query()
                ->with('records')
                ->whereExamId($exam->id)
                ->whereBatchId($batch->id)
                ->where('is_reassessment', true)
                ->whereIn('attempt', Arr::pluck($reassessmentAttempts, 'value'))
                ->get();

            $inputSchedule = $reassessmentSchedules->firstWhere('attempt.value', Arr::get($params, 'attempt'));

            if (! $inputSchedule) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('exam.schedule.reassessment')])]);
            }
        }

        $params['reassessment_attempts'] = $reassessmentAttempts;

        $examGrade = $schedule->grade;
        $failGrades = collect($examGrade->records)->where('is_fail_grade', true)->pluck('code')->all();

        $subjects = Subject::query()
            ->withSubjectRecord($batch->id, $batch->course_id)
            ->orderBy('subjects.position', 'asc')
            ->get();

        $scheduleAssessmentRecords = collect($schedule->assessment->records ?? []);
        foreach ($schedule->records as $record) {

            $subject = $subjects->firstWhere('id', $record->subject_id);

            if (! $subject) {
                continue;
            }

            $hasExam = $record->getConfig('has_exam');

            if (! $hasExam) {
                continue;
            }

            $recordMarks = $record->marks;
            $recordAssessments = $record->getConfig('assessments', []);

            $assessments = [];
            foreach ($recordAssessments as $recordAssessment) {
                $code = Arr::get($recordAssessment, 'code');

                $assessmentMaxMark = Arr::get($recordAssessment, 'max_mark', 0);
                $originalAssessmentMaxMark = $assessmentMaxMark;

                $scheduleAssessmentRecord = $scheduleAssessmentRecords->firstWhere('code', $code);
                $assessments[] = [
                    'code' => $code,
                    'name' => Arr::get($scheduleAssessmentRecord, 'name'),
                    'position' => Arr::get($scheduleAssessmentRecord, 'position', 0),
                    'max_mark' => $assessmentMaxMark,
                    'original_max_mark' => $originalAssessmentMaxMark,
                ];
            }

            $assessments = collect($assessments)->sortBy('position')->values()->all();

            $examRecords[] = [
                'exam_id' => $schedule->exam_id,
                'schedule_id' => $schedule->id,
                'subject_id' => $record->subject_id,
                'has_grading' => $subject->has_grading,
                'is_elective' => $subject->is_elective,
                'assessments' => $assessments,
                'not_applicable_students' => $record->getConfig('not_applicable_students', []),
                'marks' => $recordMarks,
            ];
        }

        $reassessmentExamRecords = [];
        if (! empty($reassessmentSchedules)) {
            foreach ($reassessmentSchedules as $reassessmentSchedule) {
                $records = $reassessmentSchedule->records->where('config.has_exam', true);
                foreach ($records as $record) {

                    $subject = $subjects->firstWhere('id', $record->subject_id);

                    if (! $subject) {
                        continue;
                    }

                    $reassessmentExamRecords[$reassessmentSchedule->attempt->value][] = [
                        'exam_id' => $reassessmentSchedule->exam_id,
                        'schedule_id' => $reassessmentSchedule->id,
                        'subject_id' => $record->subject_id,
                        'has_grading' => $subject->has_grading,
                        'is_elective' => $subject->is_elective,
                        'not_applicable_students' => $record->getConfig('not_applicable_students', []),
                        'marks' => $record->marks,
                    ];
                }
            }
        }

        $subjectWiseStudents = SubjectWiseStudent::query()
            ->whereBatchId($batch->id)
            ->whereIn('student_id', $students->pluck('id')->all())
            ->get();

        $headingRow = [];

        array_push($headingRow, [
            'key' => 'subject_code',
            'type' => 'heading',
            'label' => trans('academic.course.props.code'),
        ]);

        array_push($headingRow, [
            'key' => 'subject_title',
            'type' => 'heading',
            'label' => trans('academic.course.props.title'),
        ]);

        array_push($headingRow, [
            'key' => 'subject_credit',
            'type' => 'heading',
            'label' => trans('academic.subject.props.credit'),
        ]);

        array_push($headingRow, [
            'key' => 'grade',
            'type' => 'heading',
            'label' => trans('exam.grade.grade'),
        ]);

        array_push($headingRow, [
            'key' => 'point',
            'type' => 'heading',
            'label' => trans('exam.grade.props.point'),
        ]);

        array_push($headingRow, [
            'key' => 'credit_point',
            'type' => 'heading',
            'label' => trans('exam.grade.props.credit_point'),
        ]);

        foreach ($students as $student) {
            $rows = [];

            $rows[] = $headingRow;

            $totalCredit = 0;
            $totalObtainedCredit = 0;
            $totalGradePoint = 0;
            $failedGrade = 0;
            $totalAbsentCount = 0;
            $failedGradeSubjects = [];
            $reassessmentSubjects = [];
            $absentSubjects = [];
            foreach ($examRecords as $examRecord) {

                $notApplicableStudents = $examRecord['not_applicable_students'] ?? [];

                if (in_array($student->uuid, $notApplicableStudents)) {
                    continue;
                }

                if ($examRecord['is_elective'] && ! $subjectWiseStudents->where('student_id', $student->id)->firstWhere('subject_id', $examRecord['subject_id'])) {
                    continue;
                }

                $row = [];
                $subject = $subjects->firstWhere('id', $examRecord['subject_id']);

                array_push($row, [
                    'key' => 'subject_code',
                    'label' => $subject->code,
                ]);
                array_push($row, [
                    'key' => 'subject_title',
                    'label' => $subject->name,
                ]);
                array_push($row, [
                    'key' => 'subject_credit',
                    'label' => $subject->credit,
                ]);

                $totalCredit += $subject->credit;

                $assessments = $examRecord['assessments'];

                $attemptMarks = $this->getReassessmentMarks($student, $examRecord, $reassessmentExamRecords, $params);

                $marks = Arr::get($attemptMarks, 'marks');
                $attemptDetail = Arr::get($attemptMarks, 'attempt');

                $isAbsent = false;
                $subjectMaxMarks = 0;
                $subjectTotal = 0;
                $absentCount = 0;
                $markNotRecorded = 0;
                foreach ($assessments as $assessment) {
                    $assessmentMark = collect($marks)
                        ->firstWhere('code', $assessment['code']);

                    $obtainedMark = collect(Arr::get($assessmentMark, 'marks') ?? [])
                        ->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? '';

                    if (in_array($obtainedMark, ['A', 'a'])) {
                        $absentCount++;
                    } elseif (empty($obtainedMark)) {
                        $markNotRecorded++;
                    }

                    $subjectMaxMarks += $assessment['max_mark'];
                    $subjectTotal += is_numeric($obtainedMark) ? $obtainedMark : 0;
                }

                if (Arr::get($params, 'subject_absent_criteria') == 'all' && $absentCount == count($assessments)) {
                    $absentSubjects[] = $subject;
                    $isAbsent = true;
                } elseif (Arr::get($params, 'subject_absent_criteria') == 'any' && $absentCount) {
                    $absentSubjects[] = $subject;
                    $isAbsent = true;
                }

                $subjectGrade = $this->getGrade($schedule->grade, $subjectMaxMarks, $subjectTotal, 'grade');

                if ($isAbsent) {
                    $subjectGrade = ['code' => 'Ab', 'value' => 0];
                    $totalAbsentCount++;
                }

                array_push($row, [
                    'key' => 'grade',
                    'label' => Arr::get($subjectGrade, 'code'),
                ]);

                if (in_array(Arr::get($subjectGrade, 'code'), $failGrades)) {
                    $failedGradeSubjects[] = $subject;
                    $failedGrade++;
                }

                if (! in_array(Arr::get($subjectGrade, 'code'), $failGrades) && ! $isAbsent) {
                    $totalObtainedCredit += $subject->credit;
                }

                array_push($row, [
                    'key' => 'point',
                    'label' => (float) Arr::get($subjectGrade, 'value'),
                ]);

                if (is_numeric(Arr::get($subjectGrade, 'value'))) {
                    $creditPoint = $subject->credit * Arr::get($subjectGrade, 'value');
                } else {
                    $creditPoint = 0;
                }

                $totalGradePoint += $creditPoint;

                array_push($row, [
                    'key' => 'credit_point',
                    'label' => $creditPoint,
                ]);

                if ($attemptDetail != AssessmentAttempt::FIRST->value) {
                    $row = collect($row)->map(function ($item) {
                        $item['additional'] = 'reassessed';

                        return $item;
                    })->all();
                }

                $rows[] = $row;
            }

            $row = [
                [
                    'key' => 'subject_code',
                    'type' => 'footer',
                    'label' => trans('exam.total'),
                    'colspan' => 2,
                ],
                [
                    'key' => 'subject_credit',
                    'type' => 'footer',
                    'label' => $totalCredit,
                ],
                [
                    'key' => 'grade',
                    'type' => 'footer',
                    'label' => '',
                ],
                [
                    'key' => 'point',
                    'type' => 'footer',
                    'label' => '',
                ],
                [
                    'key' => 'credit_point',
                    'type' => 'footer',
                    'label' => $totalGradePoint,
                ],
            ];

            $rows[] = $row;

            $gpa = $totalCredit ? round($totalGradePoint / $totalCredit, 2) : '';

            $row = [
                [
                    'key' => 'subject_code',
                    'type' => 'footer',
                    'label' => trans('exam.gpa'),
                    'colspan' => 5,
                ],
                [
                    'key' => 'credit_point',
                    'type' => 'footer',
                    'label' => $gpa,
                ],
            ];

            $rows[] = $row;

            $result = $failedGrade ? Result::FAIL->value : Result::PASS->value;

            // custom result logic

            // end custom result logic

            $resultLabel = trans('exam.results.'.$result);

            $failedGradeSubjects = collect($failedGradeSubjects)->map(function ($subject) {
                return $subject->code;
            })->all();

            $scheduleConfig = $inputSchedule->config;
            $scheduleConfig['marksheet_status'] = 'processed';
            $scheduleConfig['marksheet_type'] = 'exam_wise_credit_based';
            $scheduleConfig['result_date'] = Arr::get($params, 'result_date');
            $scheduleConfig['marksheet_template'] = Arr::get($params, 'template');
            $scheduleConfig['report_type'] = ReportType::CREDIT_BASED->value;
            $inputSchedule->config = $scheduleConfig;
            $inputSchedule->save();

            $examResult = ExamResult::firstOrCreate([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'attempt' => Arr::get($params, 'attempt'),
            ]);

            $examResult->result = $result;
            $examResult->marks = $rows;
            $examResult->total_marks = $totalCredit;
            $examResult->obtained_marks = $totalObtainedCredit;
            $examResult->percentage = $gpa;
            $examResult->subjects = [
                'failed' => $failedGradeSubjects,
                'absent' => $absentSubjects,
                'reassessment' => $reassessmentSubjects,
            ];
            $examResult->generated_at = now()->toDateTimeString();
            $examResult->save();
        }

        if (Arr::get($params, 'attempt') != AssessmentAttempt::FIRST->value) {
            $removeStudentIds = array_diff($allStudents->pluck('id')->all(), $students->pluck('id')->all());

            ExamResult::query()
                ->whereExamId($exam->id)
                ->where('attempt', Arr::get($params, 'attempt'))
                ->whereIn('student_id', $removeStudentIds)
                ->delete();
        }
    }

    private function getReassessmentMarks(Student $student, array $examRecord, array $reassessmentExamRecords, array $params)
    {
        $marks = $examRecord['marks'];
        $assessments = $examRecord['assessments'];
        $reassessmentAttempts = $params['reassessment_attempts'];

        if (Arr::get($params, 'attempt') == AssessmentAttempt::FIRST->value) {
            return [
                'marks' => $marks,
                'attempt' => AssessmentAttempt::FIRST->value,
            ];
        }

        foreach ($reassessmentAttempts as $reassessmentAttempt) {
            $reassessmentExamRecord = collect($reassessmentExamRecords[$reassessmentAttempt['value']])
                ->firstWhere('subject_id', $examRecord['subject_id']);

            if (! $reassessmentExamRecord) {
                continue;
            }

            foreach ($assessments as $assessment) {
                $reassessmentMark = collect($reassessmentExamRecord['marks'])
                    ->firstWhere('code', $assessment['code']);

                if (collect(Arr::get($reassessmentMark, 'marks') ?? [])->contains('uuid', $student->uuid)) {

                    return [
                        'marks' => $reassessmentExamRecord['marks'],
                        'attempt' => $reassessmentAttempt['value'],
                    ];
                }
            }
        }

        return [
            'marks' => $marks,
            'attempt' => AssessmentAttempt::FIRST->value,
        ];
    }
}
