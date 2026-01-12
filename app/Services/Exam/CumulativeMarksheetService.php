<?php

namespace App\Services\Exam;

use App\Enums\Student\AttendanceSession;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Period;
use App\Domain\Academic\Models\Subject;
use App\Models\Exam\Exam;
use App\Models\Exam\Grade;
use App\Models\Exam\Observation;
use App\Models\Exam\Schedule;
use App\Models\Exam\Term;
use App\Models\HealthRecord;
use App\Models\Student\Attendance;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;
use App\Support\HasGrade;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CumulativeMarksheetService
{
    use HasGrade;

    public function getData(Batch $batch, Collection $students, array $params)
    {
        $params['attendance_till_last_exam_date'] = true;
        $params['student_wise_attendance_working_days'] = false;
        $params['show_total_column'] = true;
        $params['show_percentage_column'] = true;
        $params['show_grade_column'] = true;
        $params['show_grade_detail'] = true;
        $params['show_s_no'] = false;
        $params['show_assessment_wise_report'] = false;
        $params['hide_term_row'] = false;
        $params['multi_column_heading_observation_record'] = false;
        // getting from request params
        // $params['show_overall_total'] = true;
        // $params['show_overall_grade'] = true;

        if ($params['show_assessment_wise_report']) {
            $params['show_total_column'] = false;
            $params['show_percentage_column'] = false;
            $params['show_grade_column'] = false;
        }

        if (Arr::get($params, 'type') == 'term_wise' || Arr::get($params, 'type') == 'cumulative') {
            unset($params['exam']);
        }

        if (Arr::get($params, 'type') == 'exam_wise' || Arr::get($params, 'type') == 'cumulative') {
            unset($params['term']);
        }

        $terms = Term::query()
            ->with([
                'exams' => function ($q) use ($params, $batch) {
                    $q->with('schedules.assessment')
                        ->whereHas('schedules', function ($q) use ($batch) {
                            $q->where('batch_id', $batch->id);
                        })->when(Arr::get($params, 'exam'), function ($q, $exam) {
                            $q->where('uuid', $exam);
                        })->orderBy('position');
                },
            ])
            ->byPeriod()
            ->when(Arr::get($params, 'term'), function ($q, $term) {
                $q->where('uuid', $term);
            })
            ->when(Arr::get($params, 'exam'), function ($q, $exam) {
                $q->whereHas('exams', function ($q) use ($exam) {
                    $q->where('uuid', $exam);
                });
            })
            ->where(function ($q) use ($batch) {
                $q->whereDivisionId($batch->course->division_id)
                    ->orWhereNull('division_id');
            })
            ->get();

        if (! $terms->count()) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('exam.exam')])]);
        }

        $lastTerm = $terms->last();
        $lastExam = $lastTerm->exams->last();

        if ($lastExam && auth()->user()->is_student_or_guardian && ! $lastExam->getMeta('publish_marksheet')) {
            throw ValidationException::withMessages(['message' => trans('exam.marksheet.not_published')]);
        }

        $schedules = Schedule::query()
            ->with(['exam', 'assessment', 'records.subject'])
            ->whereBatchId($batch->id)
            ->when(Arr::get($params, 'exam'), function ($q, $examUuid) {
                $q->whereHas('exam', function ($q) use ($examUuid) {
                    $q->where('uuid', $examUuid);
                });
            })
            ->get();

        $lastExamSchedule = $schedules->firstWhere('exam_id', $lastExam->id);

        if (! $lastExamSchedule) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('exam.exam')])]);
        }

        $lastExamGrade = $lastExamSchedule->grade;

        $failGrades = collect($lastExamGrade->records)->where('is_fail_grade', true)->pluck('code')->all();

        $params['fail_grades'] = $failGrades;

        $lastExamDate = $lastExamSchedule->records->where('config.has_exam', true)->sortByDesc('date.value')->first()?->date;

        $healthRecords = HealthRecord::query()
            ->whereModelType('Student')
            ->whereIn('model_id', $students->pluck('id')->all())
            ->where('date', '<=', $lastExamDate->value)
            ->orderBy('date', 'desc')
            ->get();

        $comments = Arr::get($lastExamSchedule->details, 'comments', []);

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

        $examAttendances = collect(Arr::get($lastExamSchedule->details, 'attendances', []));

        $totalWorkingDays = 0;
        if (! Arr::get($params, 'student_wise_attendance_working_days')) {
            $totalWorkingDays = $overallAttendances->count();
            if (! empty(Arr::get($lastExamSchedule->details, 'total_working_days'))) {
                $totalWorkingDays = Arr::get($lastExamSchedule->details, 'total_working_days');
            }
        }

        $subjects = Subject::query()
            ->withSubjectRecord($batch->id, $batch->course_id)
            ->orderBy('subjects.position', 'asc')
            ->get();

        $mainSubjects = $subjects->where('has_grading', 0);
        $gradingSubjects = $subjects->where('has_grading', 1);

        $params['subject_wise_students'] = SubjectWiseStudent::query()
            ->whereBatchId($batch->id)
            ->whereIn('student_id', $students->pluck('id')->all())
            ->get();

        $comparableExams = [];
        $combineAssessments = [];
        $markConversion = collect([]);

        $existingExams = [];

        foreach ($terms as $term) {
            foreach ($term->exams as $exam) {
                $existingExams[] = $exam->code;
            }
        }

        $additionalExams = [];
        if ($comparableExams && $terms->count() == 1) {
            foreach ($comparableExams as $key => $comparableExam) {
                if (! in_array($key, $existingExams)) {
                    continue;
                }

                if ($comparableExam != $key) {
                    $additionalExams[] = $comparableExam;
                }
            }
        }

        $comparableSchedules = Schedule::query()
            ->with('records')
            ->where('batch_id', $batch->id)
            ->whereHas('exam', function ($q) use ($additionalExams) {
                $q->whereIn('code', $additionalExams);
            })
            ->get();

        $params['mark_conversion'] = $markConversion;

        $examRecords = [];
        foreach ($schedules as $schedule) {
            $scheduleAssessmentRecords = collect($schedule->assessment->records ?? []);
            foreach ($schedule->records as $record) {

                $subject = $subjects->firstWhere('id', $record->subject_id);

                if (! $subject) {
                    continue;
                }

                $conversion = $markConversion->firstWhere('exam', $schedule->exam->code);

                $comparableMarks = [];
                if ($comparableSchedules) {

                    foreach ($comparableExams as $key => $comparableExam) {
                        if ($schedule->exam->code == $key) {
                            $comparableSchedule = $comparableSchedules->firstWhere('exam.code', $comparableExam);

                            if ($comparableSchedule) {
                                $additionalRecord = $comparableSchedule->records->firstWhere('subject_id', $record->subject_id);

                                $comparableMarks = $additionalRecord?->marks ?? [];
                            }
                        }
                    }
                }

                $recordMarks = $record->marks;
                $recordAssessments = $record->getConfig('assessments', []);

                if (! $subject->has_grading && in_array($schedule->exam->code, array_keys($combineAssessments))) {
                    $combineAssessmentCode = Arr::get($combineAssessments, $schedule->exam->code);

                    $combineMaxMark = 0;
                    $recordMarks = [];
                    $recordAssessments = [];
                    foreach ($record->getConfig('assessments', []) as $recordAssessment) {
                        $combineMaxMark += Arr::get($recordAssessment, 'max_mark', 0);
                    }

                    $studentUuids = collect($record->marks)->pluck('marks')->collapse()->pluck('uuid')->unique()->all();

                    $studentCombineAssessmentMarks = [];
                    foreach ($studentUuids as $studentUuid) {
                        $studentAssessmentMark = 0;

                        $hasNonNumericMark = 0;
                        foreach ($record->getConfig('assessments', []) as $recordAssessment) {
                            $code = Arr::get($recordAssessment, 'code');

                            $assessmentMarks = collect($record->marks)->firstWhere('code', $code);
                            $assessmentMark = collect(Arr::get($assessmentMarks, 'marks', []))->firstWhere('uuid', $studentUuid);

                            if (is_numeric(Arr::get($assessmentMark, 'obtained_mark'))) {
                                $studentAssessmentMark += Arr::get($assessmentMark, 'obtained_mark');
                            } else {
                                $hasNonNumericMark++;
                            }
                        }

                        if ($hasNonNumericMark == count($record->getConfig('assessments', []))) {
                            $studentAssessmentMark = Arr::get($assessmentMark, 'obtained_mark');
                        }

                        $studentCombineAssessmentMarks[] = [
                            'uuid' => $studentUuid,
                            'obtained_mark' => $studentAssessmentMark,
                        ];
                    }

                    array_push($recordMarks, [
                        'code' => $combineAssessmentCode,
                        'marks' => $studentCombineAssessmentMarks,
                    ]);

                    array_push($recordAssessments, [
                        'code' => $combineAssessmentCode,
                        'max_mark' => $combineMaxMark,
                    ]);
                }

                $assessments = [];
                foreach ($recordAssessments as $recordAssessment) {
                    $code = Arr::get($recordAssessment, 'code');

                    $requiresConversion = false;
                    $roundOffConversion = false;

                    $assessmentMaxMark = Arr::get($recordAssessment, 'max_mark', 0);
                    $originalAssessmentMaxMark = $assessmentMaxMark;
                    if ($conversion) {
                        $requiresConversion = true;
                        $roundOffConversion = Arr::get($conversion, 'round_off_conversion', false);
                        $assessmentMaxMark = Arr::get(collect($conversion['assessments'])->firstWhere('code', $code), 'max_mark');
                    }

                    $scheduleAssessmentRecord = $scheduleAssessmentRecords->firstWhere('code', $code);
                    $assessments[] = [
                        'code' => $code,
                        'name' => Arr::get($scheduleAssessmentRecord, 'name'),
                        'position' => Arr::get($scheduleAssessmentRecord, 'position', 0),
                        'max_mark' => $assessmentMaxMark,
                        'requires_conversion' => $requiresConversion,
                        'round_off_conversion' => $roundOffConversion,
                        'original_max_mark' => $originalAssessmentMaxMark,
                    ];
                }

                $assessments = collect($assessments)->sortBy('position')->values()->all();

                if ($subject) {
                    $examRecords[] = [
                        'exam_id' => $schedule->exam_id,
                        'schedule_id' => $schedule->id,
                        'subject_id' => $record->subject_id,
                        'has_grading' => $subject->has_grading,
                        'assessments' => $assessments,
                        'not_applicable_students' => $record->getConfig('not_applicable_students', []),
                        'marks' => $recordMarks,
                        'comparable_marks' => $comparableMarks,
                    ];
                }
            }
        }

        $summaryHeader = [];

        array_push($summaryHeader, [
            'label' => trans('student.props.name'),
            'class' => 'font-weight-bold',
        ]);

        foreach ($terms as $term) {
            $termHeader = [
                'label' => $term->display_name ?? $term->name,
                'class' => 'font-weight-bold',
            ];

            array_push($summaryHeader, $termHeader);
        }

        array_push($summaryHeader, [
            'label' => trans('exam.total'),
            'class' => 'font-weight-bold',
        ]);

        array_push($summaryHeader, [
            'label' => trans('exam.percentage'),
            'class' => 'font-weight-bold',
        ]);

        array_push($summaryHeader, [
            'label' => trans('exam.grade.grade'),
            'class' => 'font-weight-bold',
        ]);

        $summaryRows = [];

        $observationParameterRecords = $this->getObservationParameterRecords($schedules);

        $params['observation_parameter_records'] = $observationParameterRecords;

        foreach ($students as $student) {
            $summaryRow = [];

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

            $student->attendance = [
                'present' => $totalPresent,
                'present_percentage' => $totalWorkingDays ? round(($totalPresent / $totalWorkingDays) * 100, 2) : '',
                'absent' => $totalAbsent,
                'absent_percentage' => $totalWorkingDays ? round(($totalAbsent / $totalWorkingDays) * 100, 2) : '',
                'working_days' => $totalWorkingDays,
            ];

            $comment = collect($comments)->firstWhere('uuid', $student->uuid);

            $student->comment = $comment;

            $student->rows = $this->getExamRecord($student, $terms, $mainSubjects, $lastExamGrade, $examRecords, $params);

            $student->gradingRows = $this->getGradingRecord($student, $terms, $gradingSubjects, $lastExamGrade, $examRecords, $params);

            $student->observationRows = $this->getObservationRecord($student, $terms, $schedules, $params);

            $student->health_record = $healthRecords->firstWhere('model_id', $student->id)?->details ?? [];

            array_push($summaryRow, [
                'label' => $student->name,
                'class' => 'font-weight-bold',
            ]);

            $grandTotal = 0;
            $grandMaxMark = 0;
            foreach ($student->term_summary ?? [] as $termSummary) {
                $grandTotal += $termSummary['total'];
                $grandMaxMark += $termSummary['max_mark'];
                array_push($summaryRow, [
                    'label' => $termSummary['total'],
                ]);
            }

            array_push($summaryRow, [
                'label' => $grandTotal,
            ]);

            array_push($summaryRow, [
                'label' => $grandMaxMark ? round(($grandTotal / $grandMaxMark) * 100, 2) : '',
            ]);

            array_push($summaryRow, [
                'label' => $this->getGrade($lastExamGrade, $grandMaxMark, $grandTotal),
            ]);

            array_push($summaryRows, $summaryRow);
        }

        if (Arr::get($params, 'show_summary_report')) {
            if (Arr::get($params, 'sort_summary_report_by_rank')) {
                $summaryRows = collect($summaryRows)->sortByDesc(function ($row) {
                    return $row[1]['label'];
                })->values()->all();
            }

            $meta = $this->getMeta($lastExam, $batch, $params);

            $titles = Arr::get($meta, 'titles', []);
            array_shift($titles);

            return view()->first([config('config.print.custom_path').'exam.summary-report', 'print.exam.summary-report'], [
                'summaryHeader' => $summaryHeader,
                'summaryRows' => $summaryRows,
                ...$meta,
                'titles' => $titles,
            ])->render();
        }

        return view()->first([config('config.print.custom_path').'exam.cumulative-marksheet', 'print.exam.cumulative-marksheet'], [
            'students' => $students,
            'grade' => $lastExamSchedule->grade,
            'params' => $params,
            ...$this->getMeta($lastExam, $batch, $params),
        ])->render();
    }

    private function getMetaColumn(array $params)
    {
        $column = 0;

        if (Arr::get($params, 'show_total_column')) {
            $column++;
        }

        if (Arr::get($params, 'show_percentage_column')) {
            $column++;
        }

        if (Arr::get($params, 'show_grade_column')) {
            $column++;
        }

        return $column;
    }

    private function getExamRecord(Student $student, Collection $terms, Collection $subjects, Grade $grade, array $examRecords, array $params = [])
    {
        $rows = [];

        $termHeaderRow = [];

        if (Arr::get($params, 'show_s_no')) {
            array_push($termHeaderRow, [
                'rowspan' => Arr::get($params, 'show_assessment_wise_report') ? 3 : 2,
                'label' => trans('exam.s_no'),
                'align' => 'center',
                'bold' => true,
            ]);
        }

        array_push($termHeaderRow, [
            'rowspan' => Arr::get($params, 'show_assessment_wise_report') ? 3 : 2,
            'label' => trans('academic.subject.subject'),
            'align' => 'center',
            'bold' => true,
        ]);

        $examHeaderRow = [];

        if (Arr::get($params, 'hide_term_row')) {
            array_push($examHeaderRow, [
                'rowspan' => Arr::get($params, 'show_assessment_wise_report') ? 2 : 1,
                'label' => trans('academic.subject.subject'),
                'align' => 'center',
                'bold' => true,
            ]);
        }

        $assessmentHeaderRow = [];
        foreach ($terms as $term) {
            $termColspan = 1;
            foreach ($term->exams as $exam) {
                $name = $exam->display_name ?? $exam->name;

                $examSchedule = $exam->schedules->firstWhere('batch_id', $student->batch_id);

                if (! $examSchedule) {
                    continue;
                }

                $examAssessment = $examSchedule->assessment;

                $examSubjects = collect($examRecords)
                    ->where('exam_id', $exam->id)
                    ->where('has_grading', 0);

                $subjectMaxMark = [];
                foreach ($examSubjects as $examSubject) {
                    $subjectMaxMark[] = collect(Arr::get($examSubject, 'assessments', []))->sum('max_mark');
                }

                if (count(array_unique($subjectMaxMark)) == 1) {
                    $name .= ' ('.Arr::first($subjectMaxMark).')';
                }

                if (Arr::get($params, 'show_assessment_wise_report')) {
                    $examColspan = count($examAssessment->records) + 2;
                } else {
                    $examColspan = 1;
                }

                array_push($examHeaderRow, [
                    'label' => $name,
                    'align' => 'center',
                    'bold' => true,
                    'colspan' => $examColspan,
                ]);

                $termColspan += (count($examAssessment->records) + 2);

                foreach ($examAssessment->records as $record) {
                    array_push($assessmentHeaderRow, [
                        // 'label' => Arr::get($record, 'code') . ' (' . Arr::get($record, 'max_mark') . ')',
                        'label' => Arr::get($record, 'code'),
                        'align' => 'center',
                        'bold' => true,
                    ]);
                }

                array_push($assessmentHeaderRow, [
                    'label' => trans('exam.total_short'),
                    'align' => 'center',
                    'bold' => true,
                ]);

                array_push($assessmentHeaderRow, [
                    'label' => trans('exam.grade_short'),
                    'align' => 'center',
                    'bold' => true,
                ]);
            }

            if (Arr::get($params, 'show_total_column')) {
                array_push($examHeaderRow, [
                    'label' => trans('exam.total_short'),
                    'align' => 'center',
                    'bold' => true,
                ]);
            }

            if (Arr::get($params, 'show_percentage_column')) {
                array_push($examHeaderRow, [
                    'label' => trans('exam.percentage_short'),
                    'align' => 'center',
                    'bold' => true,
                ]);
            }

            if (Arr::get($params, 'show_grade_column')) {
                array_push($examHeaderRow, [
                    'label' => trans('exam.grade_short'),
                    'align' => 'center',
                    'bold' => true,
                ]);
            }

            if (Arr::get($params, 'show_assessment_wise_report')) {
                $totalTermColspan = $termColspan + $this->getMetaColumn($params) - 1;
            } else {
                $totalTermColspan = $term->exams->count() + $this->getMetaColumn($params);
            }

            array_push($termHeaderRow, [
                'label' => $term->display_name ?? $term->name,
                'colspan' => $totalTermColspan,
                'align' => 'center',
                'bold' => true,
            ]);
        }

        if (Arr::get($params, 'show_overall_total')) {
            array_push($termHeaderRow, [
                'rowspan' => Arr::get($params, 'show_assessment_wise_report') ? 3 : 2,
                'label' => trans('exam.overall_total_short'),
                'align' => 'center',
                'bold' => true,
            ]);
        }

        if (Arr::get($params, 'show_overall_grade')) {
            array_push($termHeaderRow, [
                'rowspan' => Arr::get($params, 'show_assessment_wise_report') ? 3 : 2,
                'label' => trans('exam.grade_short'),
                'align' => 'center',
                'bold' => true,
            ]);
        }

        if (! Arr::get($params, 'hide_term_row')) {
            array_push($rows, $termHeaderRow);
        } else {
            array_push($rows, [
                [
                    'align' => 'center',
                    'font-size' => 'xl',
                    'label' => strtoupper(trans('exam.scholastic_area')),
                    'colspan' => 100,
                    'bold' => true,
                ],
            ]);
        }
        array_push($rows, $examHeaderRow);

        if (Arr::get($params, 'show_assessment_wise_report')) {
            array_push($rows, $assessmentHeaderRow);
        }

        $grandTotal = 0;
        $grandMaxMark = 0;

        $termWiseTotal = [];

        $availableSubjects = Arr::get($params, 'subject_wise_students', [])
            ->where('student_id', $student->id);

        $assessmentTotalRow = [];
        $i = 0;
        $hasFailGrade = 0;
        foreach ($subjects as $subjectIndex => $subject) {
            $overallSubjectTotal = 0;
            $overallSubjectMaxMark = 0;

            if ($subject->is_elective && ! in_array($subject->id, $availableSubjects->pluck('subject_id')->all())) {
                continue;
            }

            $row = [];

            $subjectTotal = 0;
            $subjectMaxMark = 0;

            $i++;
            if (Arr::get($params, 'show_s_no')) {
                array_push($row, ['label' => $i, 'align' => 'center']);
            }

            array_push($row, ['label' => $subject->name, 'bold' => true]);

            foreach ($terms as $term) {
                $termTotal = 0;
                $termMaxMark = 0;

                foreach ($term->exams as $exam) {

                    $examTotal = 0;
                    $examMaxMark = 0;

                    $examRecord = collect($examRecords)
                        ->where('exam_id', $exam->id)
                        ->where('subject_id', $subject->id)
                        ->first();

                    if (! $examRecord) {
                        array_push($row, ['label' => '', 'align' => 'center']);

                        continue;
                    }

                    $assessments = Arr::get($examRecord, 'assessments', []);

                    $assessmentMarks = [];
                    foreach ($assessments as $assessment) {
                        $assessmentMark = collect(Arr::get($examRecord, 'marks', []))
                            ->firstWhere('code', $assessment['code']);

                        $comparableAssessmentMark = collect(Arr::get($examRecord, 'comparable_marks', []))
                            ->firstWhere('code', $assessment['code']);

                        $obtainedMark = collect(Arr::get($assessmentMark, 'marks') ?? [])
                            ->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? '';

                        if ($comparableAssessmentMark) {
                            $comparableObtainedMark = collect(Arr::get($comparableAssessmentMark, 'marks') ?? [])
                                ->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? '';

                            if (! is_numeric($obtainedMark) && is_numeric($comparableObtainedMark)) {
                                $obtainedMark = $comparableObtainedMark;
                            } elseif (is_numeric($obtainedMark) && ! is_numeric($comparableObtainedMark)) {
                                $obtainedMark = $obtainedMark;
                            } elseif (is_numeric($obtainedMark) && is_numeric($comparableObtainedMark)) {
                                $obtainedMark = $obtainedMark > $comparableObtainedMark ? $obtainedMark : $comparableObtainedMark;
                            }
                        }

                        if (Arr::get($assessment, 'requires_conversion') && is_numeric($obtainedMark)) {
                            $obtainedMark = ($obtainedMark / Arr::get($assessment, 'original_max_mark', 0)) * Arr::get($assessment, 'max_mark', 0);

                            if (Arr::get($assessment, 'round_off_conversion')) {
                                $obtainedMark = round($obtainedMark);
                            } else {
                                $obtainedMark = round($obtainedMark, 2);
                            }
                        }

                        $examMaxMark += $assessment['max_mark'];
                        $termMaxMark += $assessment['max_mark'];
                        $subjectMaxMark += $assessment['max_mark'];
                        $grandMaxMark += $assessment['max_mark'];
                        $overallSubjectMaxMark += $assessment['max_mark'];

                        $termWiseTotal[$term->id]['max_mark'] = ($termWiseTotal[$term->id]['max_mark'] ?? 0) + $assessment['max_mark'];

                        $assessmentMarks[] = $obtainedMark;

                        if (is_numeric($obtainedMark)) {
                            $examTotal += $obtainedMark;
                            $termTotal += $obtainedMark;
                            $subjectTotal += $obtainedMark;
                            $grandTotal += $obtainedMark;
                            $overallSubjectTotal += $obtainedMark;

                            $termWiseTotal[$term->id]['total'] = ($termWiseTotal[$term->id]['total'] ?? 0) + $obtainedMark;
                        }

                        if (Arr::get($params, 'show_assessment_wise_report')) {
                            array_push($row, ['label' => $obtainedMark, 'align' => 'center']);
                        }

                        $assessmentTotalRow[] = [
                            'exam_id' => $exam->id,
                            'subject_id' => $subject->id,
                            'assessment_code' => $assessment['code'],
                            'max_mark' => $assessment['max_mark'],
                            'obtained_mark' => is_numeric($obtainedMark) ? $obtainedMark : 0,
                        ];
                    }

                    if (collect($assessmentMarks)->every(fn ($mark) => ! is_numeric($mark))) {
                        $examTotal = collect($assessmentMarks)->first();
                    }

                    array_push($row, ['label' => $examTotal, 'align' => 'center']);

                    if (Arr::get($params, 'show_assessment_wise_report')) {
                        array_push($row, ['label' => $this->getGrade($grade, $examMaxMark, $examTotal), 'align' => 'center']);
                    }
                }

                if (Arr::get($params, 'show_total_column')) {
                    array_push($row, ['label' => $termTotal, 'align' => 'center']);
                }

                if (Arr::get($params, 'show_percentage_column')) {
                    array_push($row, ['label' => $termMaxMark ? round(($termTotal / $termMaxMark) * 100, 2) : '', 'align' => 'center']);
                }

                $finalGrade = $this->getGrade($grade, $termMaxMark, $termTotal);

                if (in_array($finalGrade, Arr::get($params, 'fail_grades', []))) {
                    $hasFailGrade++;
                }

                if (Arr::get($params, 'show_grade_column')) {
                    array_push($row, ['label' => $finalGrade, 'align' => 'center']);
                }
            }

            if (Arr::get($params, 'show_overall_total')) {
                array_push($row, ['label' => $overallSubjectTotal, 'align' => 'center']);
            }

            if (Arr::get($params, 'show_overall_grade')) {
                array_push($row, ['label' => $this->getGrade($grade, $overallSubjectMaxMark, $overallSubjectTotal), 'align' => 'center']);
            }

            array_push($rows, $row);
        }

        if ($hasFailGrade) {
            $student->result = trans('exam.results.fail');
        } else {
            $student->result = trans('exam.results.pass');
        }

        $rows = $this->addAssessmentDetailSummary($student, $grade, $terms, $assessmentTotalRow, $rows, $params);

        $rows = $this->addSummary($student, $grade, $terms, $rows, $termWiseTotal, $grandMaxMark, $grandTotal, $params);

        return $rows;
    }

    private function addSummary(Student $student, Grade $grade, Collection $terms, array $rows, array $termWiseTotal, mixed $grandMaxMark, mixed $grandTotal, array $params = []): array
    {
        // if (Arr::get($params, 'show_assessment_wise_report')) {
        //     return $rows;
        // }

        $termTotalRow = [];

        array_push($termTotalRow, [
            'label' => trans('exam.total'),
            'bold' => true,
            'colspan' => Arr::get($params, 'show_s_no') ? 2 : 1,
        ]);

        $totalExams = 0;
        $grandTermTotal = 0;
        $grandTermMaxMark = 0;
        $termSummary = [];
        foreach ($terms as $term) {
            array_push($termTotalRow, [
                'label' => '',
                'colspan' => $term->exams->count(),
            ]);

            $totalExams += $term->exams->count();

            $total = $termWiseTotal[$term->id]['total'] ?? 0;
            $maxMark = $termWiseTotal[$term->id]['max_mark'] ?? 0;

            $grandTermTotal += $total;
            $grandTermMaxMark += $maxMark;

            if (Arr::get($params, 'show_total_column')) {
                array_push($termTotalRow, ['label' => $total, 'align' => 'center', 'bold' => true]);
            }

            if (Arr::get($params, 'show_percentage_column')) {
                array_push($termTotalRow, ['label' => $maxMark ? round(($total / $maxMark) * 100, 2) : '', 'align' => 'center', 'bold' => true]);
            }

            if (Arr::get($params, 'show_grade_column')) {
                array_push($termTotalRow, ['label' => $this->getGrade($grade, $maxMark, $total), 'align' => 'center', 'bold' => true]);
            }

            $termSummary[] = [
                'total' => $total,
                'max_mark' => $maxMark,
            ];
        }

        $student->max_total = $grandMaxMark;
        $student->total = $grandTotal;
        $student->grade = $this->getGrade($grade, $grandMaxMark, $grandTotal);
        $student->percentage = $grandMaxMark ? round(($grandTotal / $grandMaxMark) * 100, 2) : '';

        $student->term_summary = $termSummary;

        if (Arr::get($params, 'show_overall_total')) {
            array_push($termTotalRow, [
                'label' => $grandTotal,
                'bold' => true,
                'align' => 'center',
            ]);
        }

        if (Arr::get($params, 'show_overall_grade')) {
            array_push($termTotalRow, [
                'label' => $student->grade,
                'bold' => true,
                'align' => 'center',
            ]);
        }

        if ($terms->count() > 1 && ! Arr::get($params, 'show_assessment_wise_report')) {
            array_push($rows, $termTotalRow);
        }

        $grandTotalRow = [];

        array_push($grandTotalRow, [
            'label' => trans('exam.grand_total').' ('.$grandMaxMark.')',
            'bold' => true,
            'colspan' => $totalExams + 1 + (($terms->count() - 1) * $this->getMetaColumn($params)) + (Arr::get($params, 'show_s_no') ? 1 : 0),
        ]);

        if (Arr::get($params, 'show_total_column')) {
            array_push($grandTotalRow, ['label' => $grandTotal, 'align' => 'center', 'bold' => true]);
        }

        if (Arr::get($params, 'show_percentage_column')) {
            array_push($grandTotalRow, ['label' => $grandMaxMark ? round(($grandTotal / $grandMaxMark) * 100, 2) : '', 'align' => 'center', 'bold' => true]);
        }

        $finalGrade = $this->getGrade($grade, $grandMaxMark, $grandTotal);
        if (Arr::get($params, 'show_grade_column')) {
            array_push($grandTotalRow, ['label' => $finalGrade, 'align' => 'center', 'bold' => true]);
        }

        if (Arr::get($params, 'show_overall_total')) {
            array_push($grandTotalRow, [
                'label' => '',
                'bold' => true,
                'align' => 'center',
            ]);
        }

        if (Arr::get($params, 'show_overall_grade')) {
            array_push($grandTotalRow, [
                'label' => '',
                'bold' => true,
                'align' => 'center',
            ]);
        }

        if (! Arr::get($params, 'show_assessment_wise_report')) {
            array_push($rows, $grandTotalRow);
        }

        $student->comment = [
            'result' => Arr::get($student->comment, 'result') ?: $student->result,
            'comment' => Arr::get($student->comment, 'comment') ?: Arr::get(collect($grade->records)->firstWhere('code', $finalGrade), 'label'),
        ];

        if ($terms->count() > 1 && ! Arr::get($params, 'show_assessment_wise_report')) {
            array_push($rows, [
                [
                    'blank' => true,
                    'label' => '',
                    'colspan' => 1000,
                ],
            ]);
        }

        return $rows;
    }

    private function addAssessmentDetailSummary(Student $student, Grade $grade, Collection $terms, array $assessmentTotalRow, array $rows, array $params = []): array
    {
        if (! Arr::get($params, 'show_assessment_wise_report')) {
            return $rows;
        }

        $assessmentTotalRow = collect($assessmentTotalRow);
        $row = [];

        array_push($row, [
            'label' => trans('exam.total'),
            'bold' => true,
            'colspan' => Arr::get($params, 'show_s_no') ? 2 : 1,
        ]);
        $overallTotal = 0;
        $overallMaxTotal = 0;
        foreach ($terms as $term) {
            foreach ($term->exams as $exam) {
                $examSchedule = $exam->schedules->firstWhere('batch_id', $student->batch_id);

                if (! $examSchedule) {
                    continue;
                }

                $total = 0;
                $maxTotal = 0;
                foreach ($examSchedule->assessment->records as $record) {
                    $assessmentDetail = $assessmentTotalRow
                        ->where('exam_id', $exam->id)
                        ->where('assessment_code', Arr::get($record, 'code'));

                    $assessmentTotal = $assessmentDetail->sum('obtained_mark');
                    $assessmentMaxTotal = $assessmentDetail->sum('max_mark');

                    $total += $assessmentTotal;
                    $maxTotal += $assessmentMaxTotal;

                    $overallTotal += $assessmentTotal;
                    $overallMaxTotal += $assessmentMaxTotal;

                    array_push($row, [
                        'label' => $assessmentTotal,
                        'align' => 'center',
                        'bold' => true,
                    ]);
                }

                array_push($row, [
                    'label' => $total,
                    'align' => 'center',
                    'bold' => true,
                ]);
                array_push($row, [
                    'label' => $this->getGrade($grade, $maxTotal, $total), 'align' => 'center',
                    'bold' => true,
                ]);
            }
        }

        if (Arr::get($params, 'show_overall_total')) {
            array_push($row, [
                'label' => $overallTotal,
                'bold' => true,
                'align' => 'center',
            ]);
        }

        if (Arr::get($params, 'show_overall_grade')) {
            array_push($row, [
                'label' => $this->getGrade($grade, $overallMaxTotal, $overallTotal),
                'bold' => true,
                'align' => 'center',
            ]);
        }

        array_push($rows, $row);

        $row = [];
        $percentRow = [];

        array_push($row, [
            'label' => trans('exam.grand_total'),
            'bold' => true,
            'font-size' => 'lg',
            'rowspan' => 2,
            'colspan' => Arr::get($params, 'show_s_no') ? 2 : 1,
        ]);

        foreach ($terms as $term) {
            foreach ($term->exams as $exam) {
                $examSchedule = $exam->schedules->firstWhere('batch_id', $student->batch_id);

                if (! $examSchedule) {
                    continue;
                }

                $total = $assessmentTotalRow
                    ->where('exam_id', $exam->id)
                    ->sum('obtained_mark');

                $maxMark = $assessmentTotalRow
                    ->where('exam_id', $exam->id)
                    ->sum('max_mark');

                array_push($row, [
                    'label' => $total.' / '.$maxMark,
                    'align' => 'center',
                    'bold' => true,
                    'font-size' => 'lg',
                    'colspan' => count($examSchedule->assessment->records) + 2,
                ]);

                $percent = $maxMark ? round(($total / $maxMark) * 100, 2) : '';

                array_push($percentRow, [
                    'label' => $percent.'%',
                    'align' => 'center',
                    'bold' => true,
                    'font-size' => 'lg',
                    'colspan' => count($examSchedule->assessment->records) + 2,
                ]);
            }
        }

        if (Arr::get($params, 'show_overall_total')) {
            array_push($row, [
                'label' => $overallTotal.' / '.$overallMaxTotal,
                'align' => 'center',
                'bold' => true,
                'colspan' => 2,
            ]);
        }

        // if (Arr::get($params, 'show_overall_grade')) {
        //     array_push($row, [
        //         'label' => $this->getGrade($grade, $overallMaxTotal, $overallTotal),
        //         'align' => 'center',
        //         'bold' => true,
        //     ]);
        // }

        if (Arr::get($params, 'show_overall_total')) {
            array_push($percentRow, [
                'label' => $overallMaxTotal ? round(($overallTotal / $overallMaxTotal) * 100, 2).'%' : '',
                'align' => 'center',
                'bold' => true,
                'colspan' => 2,
            ]);
        }

        array_push($rows, $row);
        array_push($rows, $percentRow);

        return $rows;
    }

    private function getGradingRecord(Student $student, Collection $terms, Collection $subjects, Grade $grade, array $examRecords, array $params = [])
    {
        $rows = [];

        $examsIncluded = [];

        $grandTotal = 0;
        $grandMaxMark = 0;
        $i = 0;
        foreach ($subjects as $index => $subject) {
            $row = [];
            $subjectTotal = 0;
            $subjectMaxMark = 0;

            $i++;

            if (Arr::get($params, 'show_s_no')) {
                array_push($row, ['label' => $i, 'align' => 'center']);
            }

            array_push($row, ['label' => $subject->name, 'bold' => true]);

            foreach ($terms as $term) {
                $termTotal = 0;
                $termMaxMark = 0;

                foreach ($term->exams as $exam) {

                    if (count($examsIncluded) && ! in_array($exam->code, $examsIncluded)) {
                        continue;
                    }

                    $examTotal = 0;
                    $examMaxMark = 0;

                    $examRecord = collect($examRecords)
                        ->where('exam_id', $exam->id)
                        ->where('subject_id', $subject->id)
                        ->first();

                    if (! $examRecord) {
                        array_push($row, ['label' => '', 'align' => 'center']);

                        continue;
                    }

                    $assessments = Arr::get($examRecord, 'assessments', []);

                    $assessmentMarks = [];
                    foreach ($assessments as $assessment) {
                        $assessmentMark = collect(Arr::get($examRecord, 'marks', []))
                            ->firstWhere('code', $assessment['code']);

                        $obtainedMark = collect(Arr::get($assessmentMark, 'marks') ?? [])
                            ->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? '';

                        $assessmentMarks[] = $obtainedMark;

                        $examMaxMark += $assessment['max_mark'];
                        $termMaxMark += $assessment['max_mark'];
                        $subjectMaxMark += $assessment['max_mark'];
                        $grandMaxMark += $assessment['max_mark'];

                        if (is_numeric($obtainedMark)) {
                            $examTotal += $obtainedMark;
                            $termTotal += $obtainedMark;
                            $subjectTotal += $obtainedMark;
                            $grandTotal += $obtainedMark;
                        }
                    }

                    if (collect($assessmentMarks)->every(fn ($mark) => ! is_numeric($mark))) {
                        $examTotal = collect($assessmentMarks)->first();
                    }

                    array_push($row, [
                        'label' => $this->getGrade($grade, $examMaxMark, $examTotal),
                        'align' => 'center',
                        'colspan' => count($examsIncluded) ? $term->exams->count() + $this->getMetaColumn($params) : 0,
                    ]);
                }

                if (empty($examsIncluded) && $this->getMetaColumn($params)) {
                    array_push($row, ['label' => '', 'align' => 'center', 'colspan' => $this->getMetaColumn($params)]);
                }
            }

            array_push($rows, $row);
        }

        return $rows;
    }

    private function getObservationParameterRecords(Collection $schedules)
    {
        $observations = Observation::query()
            ->with('grade')
            ->whereIn('id', $schedules->pluck('observation_id')->all())
            ->get();

        $observationParameterRecords = [];
        foreach ($observations as $observation) {
            foreach ($observation->records as $observationRecord) {
                $observationParameterRecords[] = [
                    'name' => Arr::get($observationRecord, 'name'),
                    'code' => Arr::get($observationRecord, 'code'),
                    'max_mark' => Arr::get($observationRecord, 'max_mark'),
                ];
            }
        }

        return collect($observationParameterRecords)->unique('code')->all();
    }

    private function getObservationRecord(Student $student, Collection $terms, Collection $schedules, array $params)
    {
        $observationParameterRecords = Arr::get($params, 'observation_parameter_records', []);

        $multiColumnHeadingObservationRecord = Arr::get($params, 'multi_column_heading_observation_record', false);

        $observationRows = [];
        $observationHeadingRow = [];

        if (Arr::get($params, 'show_s_no')) {
            array_push($observationHeadingRow, [
                'label' => trans('exam.s_no'),
                'align' => 'center',
                'bold' => true,
            ]);
        }

        $observationHeadingNameCount = 0;
        foreach ($observationParameterRecords as $index => $observationParameterRecord) {
            $observationRow = [];

            if (Arr::get($params, 'show_s_no')) {
                array_push($observationRow, [
                    'label' => $index + 1,
                    'align' => 'center',
                ]);
            }

            if (! $multiColumnHeadingObservationRecord) {
                array_push($observationRow, [
                    'label' => Arr::get($observationParameterRecord, 'name'),
                    'bold' => true,
                ]);
            }

            foreach ($terms as $termIndex => $term) {
                foreach ($term->exams as $examIndex => $exam) {
                    $schedule = $schedules->where('exam_id', $exam->id)->first();

                    if (! $schedule) {
                        continue;
                    }

                    $observationParameterId = $schedule->observation_id;

                    if ($observationParameterId && Arr::get($schedule->details, 'observation_marks', [])) {

                        if (($multiColumnHeadingObservationRecord && $index == 0) || (! $multiColumnHeadingObservationRecord && $observationHeadingNameCount == 0)) {
                            array_push($observationHeadingRow, [
                                'label' => $schedule->observation->name,
                                'align' => 'center',
                                'bold' => true,
                            ]);
                            $observationHeadingNameCount++;
                        }

                        $maxMark = Arr::get($observationParameterRecord, 'max_mark');

                        $observationMarks = collect(Arr::get($schedule->details, 'observation_marks', []))->firstWhere('code', Arr::get($observationParameterRecord, 'code'))['marks'] ?? [];

                        $observationMark = collect($observationMarks)->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? null;

                        if ($multiColumnHeadingObservationRecord) {
                            array_push($observationRow, [
                                'label' => Arr::get($observationParameterRecord, 'name'),
                                'bold' => true,
                            ]);
                        } else {

                        }

                        $observationGrade = $schedule->observation->grade;

                        array_push($observationHeadingRow, [
                            'label' => $exam->name,
                            'align' => 'center',
                            'bold' => true,
                        ]);

                        array_push($observationRow, [
                            'label' => $observationGrade ? $this->getGrade($observationGrade, $maxMark, $observationMark) : '',
                            'align' => 'center',
                        ]);
                    }
                }
            }

            if ($index == 0) {
                array_push($observationRows, $observationHeadingRow);
            }

            array_push($observationRows, $observationRow);
        }

        return $observationRows;
    }

    private function getMeta(Exam $exam, Batch $batch, array $params)
    {
        $period = Period::find(auth()->user()->current_period_id);

        $titles = [
            [
                'label' => Arr::get($exam->config_detail, 'title'),
                'align' => 'center',
                'class' => 'heading',
            ],
            [
                'label' => $exam->name.' '.$period->code,
                'align' => 'center',
                'class' => 'mt-2 sub-heading',
            ],
            [
                'label' => $batch->course->name.' '.$batch->name,
                'align' => 'center',
                'class' => 'mt-2 sub-heading',
            ],
        ];

        $boxWidth = match ((int) Arr::get($params, 'column', 1)) {
            1 => '100%',
            2 => '48%',
            3 => '33%',
            default => '100%',
        };

        $layout = [
            'column' => Arr::get($params, 'column', 1),
            'margin_top' => Arr::get($params, 'margin_top', 0),
            'box_width' => $boxWidth,
            'show_print_date_time' => (bool) Arr::get($exam->config_detail, 'show_print_date_time'),
            'show_watermark' => (bool) Arr::get($exam->config_detail, 'show_watermark'),
            'signatory1' => Arr::get($exam->config_detail, 'signatory1'),
            'signatory2' => Arr::get($exam->config_detail, 'signatory2'),
            'signatory3' => Arr::get($exam->config_detail, 'signatory3'),
            'signatory4' => Arr::get($exam->config_detail, 'signatory4'),
        ];

        return compact('titles', 'period', 'layout');
    }
}
