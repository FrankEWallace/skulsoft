<?php

namespace App\Actions\Exam;

use App\Enums\Exam\AssessmentAttempt;
use App\Enums\Exam\ReportType;
use App\Enums\Exam\Result;
use App\Enums\Student\AttendanceSession;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Subject;
use App\Models\Exam\Exam;
use App\Models\Exam\Observation;
use App\Models\Exam\Result as ExamResult;
use App\Models\Exam\Schedule;
use App\Models\Exam\Term;
use App\Models\HealthRecord;
use App\Models\Student\Attendance;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;
use App\Support\HasGrade;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProcessTermWiseMarksheet
{
    use HasGrade;

    public function execute(Batch $batch, Collection $students, array $params)
    {
        $params['subject_absent_criteria'] = 'all'; // all | any
        $params['cumulative_assessment'] = true; // show exam wise total and percentage
        $params['overall_assessment'] = true; // Show subject wise total and percentage

        if ($params['overall_assessment']) {
            $params['cumulative_assessment'] = true;
        }

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
            ->select('exam_schedules.*')
            ->with('records', 'assessment', 'grade')
            ->join('exams', 'exam_schedules.exam_id', '=', 'exams.id')
            ->whereIn('exam_id', $exams->pluck('id')->all())
            ->whereBatchId($batch->id)
            ->where(function ($q) {
                $q->where('is_reassessment', false)
                    ->orWhere('is_reassessment', null);
            })
            ->where('attempt', AssessmentAttempt::FIRST->value)
            ->orderBy('exams.position')
            ->get();

        $lastSchedule = $schedules->last();

        $examGrade = $lastSchedule->grade;
        $failGrades = collect($examGrade->records)->where('is_fail_grade', true)->pluck('code')->all();

        $lastExamDate = $lastSchedule->records->where('config.has_exam', true)->sortByDesc('date.value')->first()?->date;

        $healthRecords = HealthRecord::query()
            ->whereModelType('Student')
            ->whereIn('model_id', $students->pluck('id')->all())
            ->where('date', '<=', $lastExamDate->value)
            ->orderBy('date', 'desc')
            ->get();

        $comments = collect(Arr::get($lastSchedule->details, 'comments', []));

        $overallAttendances = Attendance::query()
            ->whereBatchId($batch->id)
            ->whereNull('subject_id')
            ->whereSession(AttendanceSession::FIRST)
            ->whereIsDefault(true)
            ->when(Arr::get($params, 'attendance_till_last_exam_date'), function ($q) use ($lastExamDate) {
                $q->where('date', '<=', $lastExamDate->value);
            })
            ->where(function ($q) {
                $q->whereNull('meta->is_holiday')
                    ->orWhere('meta->is_holiday', false);
            })
            ->get();

        $cumulativeAttendances = [];
        foreach ($overallAttendances as $overallAttendance) {
            $values = Arr::get($overallAttendance, 'values', []);
            foreach ($values as $value) {
                foreach (Arr::get($value, 'uuids', []) as $uuid) {
                    $cumulativeAttendances[$uuid][] = Arr::get($value, 'code');
                }
            }
        }

        $examAttendances = collect(Arr::get($lastSchedule->details, 'attendances', []));

        $totalWorkingDays = 0;
        if (! Arr::get($params, 'student_wise_attendance_working_days')) {
            $totalWorkingDays = $overallAttendances->count();
            if (! empty(Arr::get($lastSchedule->details, 'total_working_days'))) {
                $totalWorkingDays = Arr::get($lastSchedule->details, 'total_working_days');
            }
        }

        $subjects = Subject::query()
            ->withSubjectRecord($batch->id, $batch->course_id)
            ->orderBy('subjects.position', 'asc')
            ->get();

        $examRecords = [];
        $examAssessments = [];
        $totalNoOfAssessment = 0;
        foreach ($schedules as $schedule) {
            $noOfAssessment = 1;
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

                if (count($recordAssessments) > $noOfAssessment) {
                    $examAssessments[$schedule->exam_id] = $recordAssessments;
                    $noOfAssessment = count($recordAssessments);
                }

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

            $totalNoOfAssessment += $noOfAssessment;
        }

        $examRecords = collect($examRecords);

        $observation = Observation::query()
            ->with('grade')
            ->whereIn('id', $lastSchedule->pluck('observation_id')->all())
            ->first();

        $observationRecords = [];
        foreach ($observation?->records ?? [] as $observationRecord) {
            $observationRecords[] = [
                'name' => Arr::get($observationRecord, 'name'),
                'code' => Arr::get($observationRecord, 'code'),
                'max_mark' => Arr::get($observationRecord, 'max_mark'),
            ];
        }
        $observationRecords = collect($observationRecords);

        $observationMarks = collect(Arr::get($schedule->details, 'observation_marks', []));

        $subjectWiseStudents = SubjectWiseStudent::query()
            ->whereBatchId($batch->id)
            ->whereIn('student_id', $students->pluck('id')->all())
            ->get();

        $headingRows = [];
        $row = [];

        array_push($row, [
            'key' => 'subject',
            'type' => 'heading',
            'label' => trans('academic.subject.subject'),
            'rowspan' => 2,
        ]);

        if (! $params['overall_assessment']) {
            $termHeadingColspan = ($totalNoOfAssessment * 2) + (count($schedules) * 2);

            if ($params['cumulative_assessment']) {
                $termHeadingColspan = (count($schedules) + 1) * 2;
            }
        } else {
            $termHeadingColspan = count($schedules) + 3;
        }

        array_push($row, [
            'key' => 'term',
            'type' => 'heading',
            'label' => $term->name,
            'colspan' => $termHeadingColspan,
        ]);

        array_push($headingRows, $row);

        $row = [];

        foreach ($exams as $exam) {
            $colspan = count($examAssessments[$exam->id] ?? [1]) * 2;

            if ($params['cumulative_assessment']) {
                $colspan = 2;
            }

            if ($params['overall_assessment']) {
                $colspan = 1;
            }

            array_push($row, [
                'key' => 'exam',
                'type' => 'heading',
                'label' => $exam->display_name ?? $exam->name,
                'colspan' => $colspan,
            ]);

            if (! $params['overall_assessment']) {
                if (! Arr::get($params, 'cumulative_assessment')) {
                    array_push($row, [
                        'key' => 'total',
                        'type' => 'heading',
                        'label' => trans('exam.total_short'),
                    ]);
                }

                array_push($row, [
                    'key' => 'grade',
                    'type' => 'heading',
                    'label' => trans('exam.grade_short'),
                ]);
            }
        }

        if ($params['overall_assessment']) {
            array_push($row, [
                'key' => 'total',
                'type' => 'heading',
                'label' => trans('exam.total_short'),
            ]);

            array_push($row, [
                'key' => 'percentage',
                'type' => 'heading',
                'label' => trans('exam.percentage_short'),
            ]);

            array_push($row, [
                'key' => 'grade',
                'type' => 'heading',
                'label' => trans('exam.grade_short'),
            ]);
        }

        array_push($headingRows, $row);

        $primarySubjects = $subjects->where('has_grading', 0);
        $gradingSubjects = $subjects->where('has_grading', 1);

        $compulsorySubjects = $primarySubjects->where('is_elective', 0);
        $electiveSubjects = $primarySubjects->where('is_elective', 1);

        foreach ($students as $student) {
            $rows = [];

            foreach ($headingRows as $headingRow) {
                array_push($rows, $headingRow);
            }

            $subjectWiseStudent = $subjectWiseStudents->filter(function ($subjectWiseStudent) use ($student, $electiveSubjects) {
                return in_array($subjectWiseStudent->subject_id, $electiveSubjects->pluck('id')->all()) && $subjectWiseStudent->student_id == $student->id;
            });

            $electedSubjects = $electiveSubjects->filter(function ($electiveSubject) use ($subjectWiseStudent) {
                return in_array($electiveSubject->id, $subjectWiseStudent->pluck('subject_id')->all());
            });

            $studentSubjects = $compulsorySubjects->merge($electedSubjects);

            $studentPrimarySubjects = $primarySubjects->filter(function ($primarySubject) use ($studentSubjects) {
                return in_array($primarySubject->id, $studentSubjects->pluck('id')->all());
            });

            $failedGrade = 0;
            $totalAbsentCount = 0;
            $absentSubjects = [];
            $failedGradeSubjects = [];
            $reassessmentSubjects = [];
            $grandMaxMarks = 0;
            $grandTotal = 0;

            $scheduleWiseTotal = [];
            foreach ($schedules as $schedule) {
                $scheduleWiseTotal[$schedule->id] = 0;
            }

            foreach ($studentPrimarySubjects as $subject) {
                $row = [];

                $subjectWiseTotal = 0;
                $subjectWiseMaxMarks = 0;

                array_push($row, [
                    'key' => 'subject',
                    'label' => $subject->name,
                ]);

                foreach ($schedules as $schedule) {
                    $examRecord = $examRecords->where('exam_id', $schedule->exam_id)
                        ->where('subject_id', $subject->id)
                        ->first();

                    if (! $examRecord) {
                        array_push($row, [
                            'key' => 'subject_total',
                            'label' => '-',
                        ]);

                        continue;
                    }

                    $assessments = $examRecord['assessments'];

                    $marks = $examRecord['marks'] ?? [];

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

                        if (Arr::get($params, 'overall_assessment')) {
                        } else {
                            if (! Arr::get($params, 'cumulative_assessment')) {
                                if (empty($assessmentMark)) {
                                    array_push($row, [
                                        'key' => 'max_mark',
                                        'label' => '-',
                                    ]);

                                    array_push($row, [
                                        'key' => 'obtained_mark',
                                        'label' => '-',
                                    ]);
                                } else {
                                    array_push($row, [
                                        'key' => 'max_mark',
                                        'label' => $assessment['max_mark'],
                                    ]);

                                    array_push($row, [
                                        'key' => 'obtained_mark',
                                        'label' => $obtainedMark,
                                    ]);
                                }
                            }
                        }

                        $subjectMaxMarks += $assessment['max_mark'];
                        $subjectTotal += is_numeric($obtainedMark) ? $obtainedMark : 0;
                    }

                    $scheduleWiseTotal[$schedule->id] += $subjectTotal;

                    $subjectWiseTotal += $subjectTotal;
                    $subjectWiseMaxMarks += $subjectMaxMarks;

                    $grandMaxMarks += $subjectMaxMarks;
                    $grandTotal += $subjectTotal;

                    if (Arr::get($params, 'overall_assessment')) {
                        array_push($row, [
                            'key' => 'subject_total',
                            'type' => 'marks',
                            'label' => $subjectTotal,
                        ]);
                    } else {
                        if (! Arr::get($params, 'cumulative_assessment')) {
                            array_push($row, [
                                'key' => 'subject_total',
                                'label' => $subjectTotal,
                            ]);
                        }

                        if (Arr::get($params, 'cumulative_assessment')) {
                            array_push($row, [
                                'key' => 'max_mark',
                                'label' => $subjectMaxMarks,
                            ]);

                            array_push($row, [
                                'key' => 'obtained_mark',
                                'label' => $subjectTotal,
                            ]);
                        }
                    }

                    if (Arr::get($params, 'subject_absent_criteria') == 'all' && $absentCount == count($assessments)) {
                        $absentSubjects[] = $subject->code;
                        $isAbsent = true;
                    } elseif (Arr::get($params, 'subject_absent_criteria') == 'any' && $absentCount) {
                        $absentSubjects[] = $subject->code;
                        $isAbsent = true;
                    }

                    $subjectGrade = $this->getGrade($schedule->grade, $subjectMaxMarks, $subjectTotal, 'grade');

                    if ($isAbsent) {
                        $subjectGrade = ['code' => 'Ab', 'value' => 0];
                        $totalAbsentCount++;
                    }

                    if (! Arr::get($params, 'overall_assessment')) {
                        array_push($row, [
                            'key' => 'obtained_grade',
                            'label' => Arr::get($subjectGrade, 'code'),
                        ]);
                    }

                    if (in_array(Arr::get($subjectGrade, 'code'), $failGrades)) {
                        $failedGradeSubjects[] = $subject->code;
                        $failedGrade++;
                    }
                }

                $subjectWisePercentage = $subjectWiseMaxMarks ? round(($subjectWiseTotal / $subjectWiseMaxMarks) * 100, 2) : 0;
                $subjectWiseGrade = $this->getGrade($lastSchedule->grade, $subjectWiseMaxMarks, $subjectWiseTotal, 'grade');

                if (Arr::get($params, 'overall_assessment')) {
                    array_push($row, [
                        'key' => 'subject_total',
                        'type' => 'summary',
                        'label' => $subjectWiseTotal,
                    ]);

                    array_push($row, [
                        'key' => 'subject_percentage',
                        'type' => 'summary',
                        'label' => $subjectWisePercentage,
                    ]);

                    array_push($row, [
                        'key' => 'obtained_grade',
                        'type' => 'summary',
                        'label' => Arr::get($subjectWiseGrade, 'code'),
                    ]);
                }

                $rows[] = $row;
            }

            $finalPercentage = $grandMaxMarks ? round(($grandTotal / $grandMaxMarks) * 100, 2) : 0;

            $result = $failedGrade ? Result::FAIL->value : Result::PASS->value;

            $finalGrade = $this->getGrade($lastSchedule->grade, $grandMaxMarks, $grandTotal, 'code');

            // custom result logic

            // end custom result logic

            $resultLabel = trans('exam.results.'.$result);

            $row = [];

            array_push($row, [
                'key' => 'total',
                'type' => 'footer',
                'label' => trans('exam.total'),
            ]);

            foreach ($schedules as $schedule) {
                array_push($row, [
                    'key' => 'schedule_total_'.$schedule->id,
                    'type' => 'footer',
                    'label' => $scheduleWiseTotal[$schedule->id] ?? 0,
                ]);
            }

            array_push($row, [
                'key' => 'total_marks',
                'type' => 'footer',
                'label' => $grandMaxMarks,
            ]);

            array_push($row, [
                'key' => 'percentage',
                'type' => 'footer',
                'label' => $finalPercentage,
            ]);

            array_push($row, [
                'key' => 'grade',
                'type' => 'footer',
                'label' => $finalGrade,
            ]);

            $rows[] = $row;

            $gradingRows = $this->getGradingRows($student, $lastSchedule, $gradingSubjects, $subjects, $subjectWiseStudents);

            $observationRows = $this->getObservationRows($student, $observation, $observationRecords, $observationMarks);

            $scheduleConfig = $lastSchedule->config;
            $scheduleConfig['marksheet_status'] = 'processed';
            $scheduleConfig['marksheet_type'] = 'term_wise';
            $scheduleConfig['result_date'] = Arr::get($params, 'result_date');
            $scheduleConfig['marksheet_template'] = Arr::get($params, 'template');
            $scheduleConfig['report_type'] = ReportType::MARK_BASED->value;
            $lastSchedule->config = $scheduleConfig;
            $lastSchedule->save();

            $examResult = ExamResult::firstOrCreate([
                'student_id' => $student->id,
                'term_id' => $term->id,
                'attempt' => Arr::get($params, 'attempt'),
            ]);

            if (Arr::get($params, 'student_wise_attendance_working_days')) {
                $totalWorkingDays = $overallAttendances->where('date.value', '>=', $student->start_date->value)->count();
            }

            $totalPresent = collect($cumulativeAttendances[$student->uuid] ?? [])->filter(function ($value) {
                return in_array($value, ['P', 'L', 'HD']);
            })->count();

            $totalAbsent = $totalWorkingDays - $totalPresent;

            $examAttendance = $examAttendances->firstWhere('uuid', $student->uuid);

            if (! empty(Arr::get($examAttendance, 'attendance'))) {
                $totalPresent = Arr::get($examAttendance, 'attendance');
                $totalAbsent = $totalWorkingDays - $totalPresent;
            }

            $examResult->setMeta([
                'grading_marks' => $gradingRows,
                'observation_marks' => $observationRows,
                'attendance' => [
                    'present' => $totalPresent,
                    'present_percentage' => $totalWorkingDays ? round(($totalPresent / $totalWorkingDays) * 100, 2) : '',
                    'absent' => $totalAbsent,
                    'absent_percentage' => $totalWorkingDays ? round(($totalAbsent / $totalWorkingDays) * 100, 2) : '',
                    'working_days' => $totalWorkingDays,
                ],
                'comment' => $comments->firstWhere('uuid', $student->uuid),
                'health_record' => $healthRecords->firstWhere('model_id', $student->id)?->details ?? [],
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
                ->whereTermId($term->id)
                ->where('attempt', Arr::get($params, 'attempt'))
                ->whereNotIn('student_id', $removeStudentIds)
                ->delete();
        }
    }

    private function getGradingRows(Student $student, Schedule $schedule, Collection $gradingSubjects, Collection $subjects, Collection $subjectWiseStudents)
    {
        $gradingRows = [];
        foreach ($gradingSubjects as $examRecord) {

            if ($examRecord['is_elective'] && ! $subjectWiseStudents->where('student_id', $student->id)->firstWhere('subject_id', $examRecord['subject_id'])) {
                continue;
            }

            $row = [];
            $subject = $subjects->firstWhere('id', $examRecord['subject_id']);

            if (! $subject) {
                continue;
            }

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

    private function getObservationRows(Student $student, ?Observation $observation, Collection $observationRecords, Collection $observationMarks)
    {
        $observationRows = [];

        if (! $observation) {
            return $observationRows;
        }

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
}
