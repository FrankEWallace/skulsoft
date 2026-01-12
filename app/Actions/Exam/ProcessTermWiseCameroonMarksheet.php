<?php

namespace App\Actions\Exam;

use App\Enums\Exam\AssessmentAttempt;
use App\Enums\Exam\ReportType;
use App\Enums\Exam\Result;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Subject;
use App\Models\Exam\Exam;
use App\Models\Exam\Observation;
use App\Models\Exam\Result as ExamResult;
use App\Models\Exam\Schedule;
use App\Models\Exam\Term;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;
use App\Support\HasGrade;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProcessTermWiseCameroonMarksheet
{
    use HasGrade;

    public function execute(Batch $batch, Collection $students, array $params = [])
    {
        $params['subject_absent_criteria'] = 'all'; // all | any
        $params['cumulative_assessment'] = false;

        $allStudents = $students;

        $term = Term::query()
            ->byPeriod()
            ->where('uuid', Arr::get($params, 'term'))
            ->firstOrFail();

        $exams = Exam::query()
            ->byPeriod()
            ->where('term_id', $term->id)
            ->get();

        $schedules = Schedule::query()
            ->with('records', 'assessment', 'grade')
            ->whereIn('exam_id', $exams->pluck('id')->all())
            ->whereBatchId($batch->id)
            ->where(function ($q) {
                $q->where('is_reassessment', false)
                    ->orWhere('is_reassessment', null);
            })
            ->where('attempt', AssessmentAttempt::FIRST->value)
            ->get();

        if (! $schedules->count()) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('exam.exam')])]);
        }

        // $inputSchedule = $schedules->first();

        // $reassessmentSchedules = null;
        // $reassessmentAttempts = [];
        // if (Arr::get($params, 'attempt') != AssessmentAttempt::FIRST->value) {
        //     $reassessmentNumber = AssessmentAttempt::getAttemptNumber(Arr::get($params, 'attempt'));

        //     $previousReassessmentNumber = $reassessmentNumber - 1;

        //     $previousAttempt = AssessmentAttempt::getAttempt($previousReassessmentNumber);

        //     $previousExamResults = ExamResult::query()
        //         ->whereExamId($exam->id)
        //         ->whereAttempt($previousAttempt)
        //         ->whereIn('student_id', $students->pluck('id')->all())
        //         ->get();

        //     $reassessmentResults = $previousExamResults->where('result.value', Result::REASSESSMENT->value);

        //     $students = $students->filter(function ($student) use ($reassessmentResults) {
        //         return $reassessmentResults->contains('student_id', $student->id);
        //     });

        //     for ($i = $reassessmentNumber; $i > 1; $i--) {
        //         $reassessmentAttempts[] = [
        //             'number' => $i,
        //             'value' => AssessmentAttempt::getAttempt($i),
        //         ];
        //     }

        //     $reassessmentSchedules = Schedule::query()
        //         ->with('records')
        //         ->whereExamId($exam->id)
        //         ->whereBatchId($batch->id)
        //         ->where('is_reassessment', true)
        //         ->whereIn('attempt', Arr::pluck($reassessmentAttempts, 'value'))
        //         ->get();

        //     $inputSchedule = $reassessmentSchedules->firstWhere('attempt.value', Arr::get($params, 'attempt'));

        //     if (! $inputSchedule) {
        //         throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('exam.schedule.reassessment')])]);
        //     }
        // }

        // $params['reassessment_attempts'] = $reassessmentAttempts;

        $lastSchedule = $schedules->last();

        $examGrade = $lastSchedule->grade;
        $failGrades = collect($examGrade->records)->where('is_fail_grade', true)->pluck('code')->all();

        $subjects = Subject::query()
            ->withSubjectRecord($batch->id, $batch->course_id)
            ->orderBy('subjects.position', 'asc')
            ->get();

        $examRecords = [];
        foreach ($schedules as $schedule) {
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
        }

        $primarySubjects = collect($examRecords)
            ->where('schedule_id', $schedules->first()->id)
            ->where('has_grading', 0)
            ->pluck('subject_id')
            ->all();

        // $reassessmentExamRecords = [];
        // if (! empty($reassessmentSchedules)) {
        //     foreach ($reassessmentSchedules as $reassessmentSchedule) {
        //         $records = $reassessmentSchedule->records->where('config.has_exam', true);
        //         foreach ($records as $record) {

        //             $subject = $subjects->firstWhere('id', $record->subject_id);

        //             if (! $subject) {
        //                 continue;
        //             }

        //             $reassessmentExamRecords[$reassessmentSchedule->attempt->value][] = [
        //                 'exam_id' => $reassessmentSchedule->exam_id,
        //                 'schedule_id' => $reassessmentSchedule->id,
        //                 'subject_id' => $record->subject_id,
        //                 'has_grading' => $subject->has_grading,
        //                 'is_elective' => $subject->is_elective,
        //                 'not_applicable_students' => $record->getConfig('not_applicable_students', []),
        //                 'marks' => $record->marks,
        //             ];
        //         }
        //     }
        // }

        // $observation = Observation::query()
        //     ->with('grade')
        //     ->whereIn('id', $schedule->pluck('observation_id')->all())
        //     ->first();

        // $observationRecords = [];
        // foreach ($observation?->records ?? [] as $observationRecord) {
        //     $observationRecords[] = [
        //         'name' => Arr::get($observationRecord, 'name'),
        //         'code' => Arr::get($observationRecord, 'code'),
        //         'max_mark' => Arr::get($observationRecord, 'max_mark'),
        //     ];
        // }
        // $observationRecords = collect($observationRecords);

        // $observationMarks = collect(Arr::get($schedule->details, 'observation_marks', []));

        $subjectWiseStudents = SubjectWiseStudent::query()
            ->whereBatchId($batch->id)
            ->whereIn('student_id', $students->pluck('id')->all())
            ->get();

        $headingRow = [];

        array_push($headingRow, [
            'key' => 'subject',
            'type' => 'heading',
            'label' => trans('academic.subject.subject'),
        ]);

        array_push($headingRow, [
            'key' => 'assessment',
            'type' => 'heading',
            'label' => trans('exam.assessment.assessment'),
        ]);

        array_push($headingRow, [
            'key' => 'max_mark',
            'type' => 'heading',
            'label' => trans('exam.max_mark'),
        ]);

        foreach ($exams as $exam) {
            array_push($headingRow, [
                'key' => 'exam_'.$exam->id,
                'type' => 'heading',
                'label' => $exam->name,
            ]);
        }

        array_push($headingRow, [
            'key' => 'term_'.$term->id,
            'type' => 'heading',
            'label' => $term->name,
        ]);

        array_push($headingRow, [
            'key' => 'obtained_grade',
            'type' => 'heading',
            'label' => trans('exam.grade.grade'),
        ]);

        foreach ($students as $student) {
            $rows = [];

            $rows[] = $headingRow;

            foreach ($primarySubjects as $primarySubject) {
                $assessmentRow = [];
                $subject = $subjects->firstWhere('id', $primarySubject);

                foreach ($schedules as $scheduleIndex => $schedule) {
                    $examRow = [];
                    $scheduleAssessmentRecords = collect($schedule->assessment->records ?? []);

                    $assessmentRecords = [];

                    foreach ($scheduleAssessmentRecords as $assessmentRecord) {
                        $assessmentRecords[$assessmentRecord['code']]['max_mark'] = 0;
                        $assessmentRecords[$assessmentRecord['code']]['obtained_mark'] = 0;
                    }

                    $examRecord = collect($examRecords)
                        ->where('exam_id', $schedule->exam_id)
                        ->where('subject_id', $subject->id)
                        ->first();

                    if (! $examRecord) {
                        continue;
                    }

                    $assessments = collect($examRecord['assessments'])->filter(function ($assessment) {
                        return $assessment['max_mark'] > 0;
                    })->values()->all();

                    $marks = $examRecord['marks'];

                    $subjectMaxMarks = 0;
                    $subjectTotal = 0;
                    foreach ($assessments as $assessment) {
                        $assessmentMark = collect($marks)
                            ->firstWhere('code', $assessment['code']);

                        $obtainedMark = collect(Arr::get($assessmentMark, 'marks') ?? [])
                            ->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? '';

                        $assessmentRow[] = [
                            'exam_id' => $schedule->exam_id,
                            'assessment' => $assessment['code'],
                            'max_mark' => $assessment['max_mark'],
                            'mark' => $obtainedMark,
                        ];

                        $subjectMaxMarks += $assessment['max_mark'];
                        $subjectTotal += is_numeric($obtainedMark) ? $obtainedMark : 0;
                    }
                }

                foreach ($assessments as $assessmentIndex => $assessment) {
                    $row = [];

                    if ($assessmentIndex == 0) {
                        array_push($row, [
                            'key' => 'subject',
                            'label' => $subject->name,
                            'rowspan' => count($assessments) + 1,
                        ]);
                    }

                    array_push($row, [
                        'key' => 'assessment',
                        'label' => $assessment['name'],
                    ]);

                    array_push($row, [
                        'key' => 'max_mark',
                        'label' => $assessment['max_mark'],
                    ]);

                    foreach ($schedules as $schedule) {
                        array_push($row, [
                            'key' => 'obtained_mark',
                            'label' => collect($assessmentRow)->where('exam_id', $schedule->exam_id)->where('assessment', $assessment['code'])->first()['mark'] ?? '',
                        ]);
                    }

                    $termAverage = collect($assessmentRow)->where('assessment', $assessment['code'])->filter(function ($item) {
                        return is_numeric($item['mark']);
                    })->average('mark');

                    array_push($row, [
                        'key' => 'term_avg',
                        'label' => $termAverage,
                    ]);

                    array_push($row, [
                        'key' => 'obtained_grade',
                        'label' => $this->getGrade($schedule->grade, $assessment['max_mark'], $termAverage, 'code'),
                    ]);

                    $rows[] = $row;
                }

                $row = [];

                array_push($row, [
                    'key' => 'total',
                    'label' => trans('exam.total'),
                ]);

                array_push($row, [
                    'key' => 'subject_max_mark',
                    'label' => $subjectMaxMarks,
                ]);

                foreach ($schedules as $schedule) {
                    array_push($row, [
                        'key' => 'subject_total',
                        'label' => collect($assessmentRow)->where('exam_id', $schedule->exam_id)->filter(function ($item) {
                            return is_numeric($item['mark']);
                        })->sum('mark'),
                    ]);
                }

                array_push($row, [
                    'key' => 'subject_total',
                    'label' => $subjectTotal,
                ]);

                array_push($row, [
                    'key' => 'subject_grade',
                    'label' => $this->getGrade($schedule->grade, $subjectMaxMarks, $subjectTotal, 'code'),
                ]);

                $rows[] = $row;
            }

            $examResult = ExamResult::firstOrCreate([
                'student_id' => $student->id,
                'term_id' => $term->id,
                'attempt' => Arr::get($params, 'attempt'),
            ]);

            $examResult->setMeta([
                'grading_marks' => [],
                'observation_marks' => [],
            ]);

            $examResult->result = Result::PASS->value;
            $examResult->marks = $rows;
            $examResult->total_marks = 0;
            $examResult->obtained_marks = 0;
            $examResult->percentage = 0;
            $examResult->subjects = [
                'failed' => [],
                'absent' => [],
                'reassessment' => [],
            ];
            $examResult->generated_at = now()->toDateTimeString();
            $examResult->setMeta([
                'result_date' => Arr::get($params, 'result_date'),
                'template' => Arr::get($params, 'template'),
                'report_type' => ReportType::MARK_BASED->value,
            ]);
            $examResult->save();
        }

        return;

        foreach ($students as $student) {
            $rows = [];

            $rows[] = $headingRow;

            $grandTermMaxMarks = 0;
            $grandTermTotal = 0;
            foreach ($schedules as $scheduleIndex => $schedule) {
                $scheduleAssessmentRecords = collect($schedule->assessment->records ?? []);

                $assessmentRecords = [];

                foreach ($scheduleAssessmentRecords as $assessmentRecord) {
                    $assessmentRecords[$assessmentRecord['code']]['max_mark'] = 0;
                    $assessmentRecords[$assessmentRecord['code']]['obtained_mark'] = 0;
                }

                $examRecords = collect($examRecords)->where('exam_id', $schedule->exam_id);

                $primaryExamRecords = collect($examRecords)->where('has_grading', 0);
                $gradingExamRecords = collect($examRecords)->where('has_grading', 1);

                $failedGrade = 0;
                $totalAbsentCount = 0;
                $absentSubjects = [];
                $failedGradeSubjects = [];
                $reassessmentSubjects = [];
                $grandMaxMarks = 0;
                $grandTotal = 0;
                foreach ($primaryExamRecords as $examRecord) {

                    $row = [];

                    if ($scheduleIndex == 0) {
                        array_push($row, [
                            'key' => 'subject',
                            'label' => $subject->name,
                            'rowspan' => count($assessments) + 1,
                        ]);
                    }

                    $notApplicableStudents = $examRecord['not_applicable_students'] ?? [];

                    if (in_array($student->uuid, $notApplicableStudents)) {
                        continue;
                    }

                    if ($examRecord['is_elective'] && ! $subjectWiseStudents->where('student_id', $student->id)->firstWhere('subject_id', $examRecord['subject_id'])) {
                        continue;
                    }

                    $subject = $subjects->firstWhere('id', $examRecord['subject_id']);

                    $assessments = collect($examRecord['assessments'])->filter(function ($assessment) {
                        return $assessment['max_mark'] > 0;
                    })->values()->all();

                    // $attemptMarks = $this->getReassessmentMarks($student, $examRecord, $reassessmentExamRecords, $params);

                    // $marks = Arr::get($attemptMarks, 'marks');
                    // $attemptDetail = Arr::get($attemptMarks, 'attempt');

                    $marks = $examRecord['marks'];

                    $isAbsent = false;
                    $subjectMaxMarks = 0;
                    $subjectTotal = 0;
                    $absentCount = 0;
                    $markNotRecorded = 0;
                    foreach ($assessments as $assessmentIndex => $assessment) {

                        if ($scheduleIndex == 0) {
                            array_push($row, [
                                'key' => 'assessment',
                                'label' => $assessment['name'],
                            ]);

                            array_push($row, [
                                'key' => 'max_mark',
                                'label' => $assessment['max_mark'],
                            ]);
                        }

                        $assessmentMark = collect($marks)
                            ->firstWhere('code', $assessment['code']);

                        $obtainedMark = collect(Arr::get($assessmentMark, 'marks') ?? [])
                            ->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? '';

                        if (in_array($obtainedMark, ['A', 'a'])) {
                            $absentCount++;
                        } elseif (empty($obtainedMark)) {
                            $markNotRecorded++;
                        }

                        array_push($row, [
                            'key' => 'obtained_mark',
                            'label' => $obtainedMark,
                        ]);

                        // array_push($row, [
                        //     'key' => 'obtained_grade',
                        //     'label' => $this->getGrade($schedule->grade, $assessment['max_mark'], $obtainedMark, 'code'),
                        // ]);

                        $subjectMaxMarks += $assessment['max_mark'];
                        $subjectTotal += is_numeric($obtainedMark) ? $obtainedMark : 0;

                        $assessmentRecords[$assessment['code']]['max_mark'] += $assessment['max_mark'];
                        $assessmentRecords[$assessment['code']]['obtained_mark'] += is_numeric($obtainedMark) ? $obtainedMark : 0;

                        $rows[] = $row;
                    }

                    // $row = [];

                    // array_push($row, [
                    //     'key' => 'total',
                    //     'label' => trans('exam.total'),
                    // ]);

                    // array_push($row, [
                    //     'key' => 'subject_max_mark',
                    //     'label' => $subjectMaxMarks,
                    // ]);

                    // array_push($row, [
                    //     'key' => 'subject_total',
                    //     'label' => $subjectTotal,
                    // ]);

                    // array_push($row, [
                    //     'key' => 'subject_grade',
                    //     'label' => $this->getGrade($schedule->grade, $subjectMaxMarks, $subjectTotal, 'code'),
                    // ]);

                    // $rows[] = $row;

                    // $grandMaxMarks += $subjectMaxMarks;
                    // $grandTotal += $subjectTotal;

                    // if (Arr::get($params, 'subject_absent_criteria') == 'all' && $absentCount == count($assessments)) {
                    //     $absentSubjects[] = $subject;
                    //     $isAbsent = true;
                    // } elseif (Arr::get($params, 'subject_absent_criteria') == 'any' && $absentCount) {
                    //     $absentSubjects[] = $subject;
                    //     $isAbsent = true;
                    // }

                    // $subjectGrade = $this->getGrade($schedule->grade, $subjectMaxMarks, $subjectTotal, 'grade');

                    // if ($isAbsent) {
                    //     $subjectGrade = ['code' => 'Ab', 'value' => 0];
                    //     $totalAbsentCount++;
                    // }

                    // if (in_array(Arr::get($subjectGrade, 'code'), $failGrades)) {
                    //     $failedGradeSubjects[] = $subject;
                    //     $failedGrade++;
                    // }
                }
            }

            // $finalGrade = $this->getGrade($schedule->grade, $grandMaxMarks, $grandTotal, 'grade');

            // $row = [];

            // array_push($row, [
            //     'key' => 'total',
            //     'type' => 'footer',
            //     'label' => trans('exam.total'),
            // ]);

            // if (Arr::get($params, 'cumulative_assessment')) {
            //     array_push($row, [
            //         'key' => 'grand_max_mark',
            //         'type' => 'footer',
            //         'label' => $grandMaxMarks,
            //     ]);

            //     array_push($row, [
            //         'key' => 'grand_total',
            //         'type' => 'footer',
            //         'label' => $grandTotal,
            //     ]);
            // } else {
            //     foreach ($scheduleAssessmentRecords as $assessmentRecord) {
            //         array_push($row, [
            //             'key' => 'max_mark',
            //             'type' => 'footer',
            //             'label' => $assessmentRecords[$assessmentRecord['code']]['max_mark'],
            //         ]);

            //         array_push($row, [
            //             'key' => 'obtained_mark',
            //             'type' => 'footer',
            //             'label' => $assessmentRecords[$assessmentRecord['code']]['obtained_mark'],
            //         ]);
            //     }

            //     array_push($row, [
            //         'key' => 'subject_total',
            //         'type' => 'footer',
            //         'label' => $grandTotal,
            //     ]);
            // }

            // array_push($row, [
            //     'key' => 'final_grade',
            //     'type' => 'footer',
            //     'label' => Arr::get($finalGrade, 'code'),
            // ]);

            // $rows[] = $row;

            // logger($rows);
            throw ValidationException::withMessages(['message' => 'test']);
            $finalPercentage = $grandMaxMarks ? round(($grandTotal / $grandMaxMarks) * 100, 2) : 0;

            $result = $failedGrade ? Result::FAIL->value : Result::PASS->value;

            // custom result logic

            // end custom result logic

            $resultLabel = trans('exam.results.'.$result);

            $gradingRows = $this->getGradingRows($student, $schedule, $gradingExamRecords, $subjects, $subjectWiseStudents);

            // $observationRows = $this->getObservationRows($student, $observation, $observationRecords, $observationMarks);

            $failedGradeSubjects = collect($failedGradeSubjects)->map(function ($subject) {
                return $subject->code;
            })->all();

            $scheduleConfig = $inputSchedule->config;
            $scheduleConfig['marksheet_status'] = 'processed';
            $scheduleConfig['marksheet_type'] = 'term_wise_cameroon';
            $scheduleConfig['result_date'] = Arr::get($params, 'result_date');
            $scheduleConfig['marksheet_template'] = Arr::get($params, 'template');
            $scheduleConfig['report_type'] = ReportType::MARK_BASED->value;
            $inputSchedule->config = $scheduleConfig;
            $inputSchedule->save();

            $examResult = ExamResult::firstOrCreate([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'attempt' => Arr::get($params, 'attempt'),
            ]);

            $examResult->setMeta([
                'grading_marks' => $gradingRows,
                'observation_marks' => $observationRows,
            ]);

            $examResult->result = $result;
            $examResult->marks = $rows;
            $examResult->total_marks = $grandMaxMarks;
            $examResult->obtained_marks = $grandTotal;
            $examResult->percentage = $finalPercentage;
            $examResult->subjects = [
                'failed' => $failedGradeSubjects,
                'absent' => $absentSubjects,
                'reassessment' => $reassessmentSubjects,
            ];
            $examResult->generated_at = now()->toDateTimeString();
            $examResult->save();
        }

        if (Arr::get($params, 'attempt') != AssessmentAttempt::FIRST->value) {
            $removeStudentIds = $students->pluck('id')->diff($allStudents->pluck('id'))->all();

            ExamResult::query()
                ->whereExamId($exam->id)
                ->where('attempt', Arr::get($params, 'attempt'))
                ->whereNotIn('student_id', $removeStudentIds)
                ->delete();
        }
    }

    private function getGradingRows(Student $student, Schedule $schedule, Collection $gradingExamRecords, Collection $subjects, Collection $subjectWiseStudents)
    {
        $gradingRows = [];
        foreach ($gradingExamRecords as $examRecord) {

            if ($examRecord['is_elective'] && ! $subjectWiseStudents->where('student_id', $student->id)->firstWhere('subject_id', $examRecord['subject_id'])) {
                continue;
            }

            $row = [];
            $subject = $subjects->firstWhere('id', $examRecord['subject_id']);

            array_push($row, [
                'key' => 'subject',
                'label' => $subject->name,
            ]);

            $assessments = $examRecord['assessments'];

            $subjectMaxMarks = 0;
            $subjectTotal = 0;
            foreach ($assessments as $assessment) {
                $assessmentMark = collect(Arr::get($examRecord, 'marks', []))
                    ->firstWhere('code', $assessment['code']);

                $obtainedMark = collect(Arr::get($assessmentMark, 'marks') ?? [])
                    ->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? '';

                $subjectMaxMarks += $assessment['max_mark'];
                $subjectTotal += is_numeric($obtainedMark) ? $obtainedMark : 0;
            }

            $subjectGrade = $this->getGrade($schedule->grade, $subjectMaxMarks, $subjectTotal, 'code');

            array_push($row, [
                'key' => 'obtained_grade',
                'label' => $subjectGrade,
            ]);

            $gradingRows[] = $row;
        }

        return $gradingRows;
    }

    private function getObservationRows(Student $student, Observation $observation, Collection $observationRecords, Collection $observationMarks)
    {
        $observationRows = [];

        foreach ($observationRecords as $index => $observationParameterRecord) {
            $observationRow = [];

            $observationRow[] = [
                'label' => Arr::get($observationParameterRecord, 'name'),
                'class' => 'text-center',
            ];

            $observationMaxMark = Arr::get($observationParameterRecord, 'max_mark');

            $observationMark = $observationMarks->firstWhere('code', Arr::get($observationParameterRecord, 'code'))['marks'] ?? [];

            $obtainedObservationMark = collect($observationMark)->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? '';

            $observationRow[] = [
                'label' => $this->getGrade($observation->grade, $observationMaxMark, $obtainedObservationMark),
                'class' => 'text-center',
            ];

            $observationRows[] = $observationRow;
        }

        return $observationRows;
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
