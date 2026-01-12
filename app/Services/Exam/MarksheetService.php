<?php

namespace App\Services\Exam;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Enums\Student\AttendanceSession;
use App\Http\Resources\Exam\ExamResource;
use App\Http\Resources\Exam\TermResource;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Period;
use App\Domain\Academic\Models\SubjectRecord;
use App\Models\Exam\Exam;
use App\Models\Exam\Observation;
use App\Models\Exam\Schedule;
use App\Models\Exam\Term;
use App\Models\HealthRecord;
use App\Models\Student\Attendance;
use App\Support\HasGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarksheetService
{
    use HasGrade;

    public function preRequisite(Request $request)
    {
        $types = [
            ['label' => trans('exam.marksheet.cumulative'), 'value' => 'cumulative'],
            ['label' => trans('exam.marksheet.term_wise'), 'value' => 'term_wise'],
            ['label' => trans('exam.marksheet.exam_wise_credit_based'), 'value' => 'exam_wise_credit_based'],
            ['label' => trans('exam.marksheet.exam_wise'), 'value' => 'exam_wise'],
            ['label' => trans('exam.marksheet.exam_wise_default'), 'value' => 'exam_wise_default'],
        ];

        $terms = TermResource::collection(Term::query()
            ->with('division')
            ->byPeriod()
            ->get());

        $exams = ExamResource::collection(Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->get());

        return compact('types', 'terms', 'exams');
    }

    public function fetchReport(Request $request)
    {
        $request->validate([
            'type' => 'required|in:cumulative,term_wise,exam_wise,exam_wise_credit_based,exam_wise_default',
            'term' => 'uuid|required_if:type,term_wise',
            'exam' => 'uuid|required_if:type,exam_wise,exam_wise_credit_based,exam_wise_default',
            'batch' => 'required|uuid',
            'students' => 'nullable',
            'column' => 'required|integer|min:1|max:2',
            'margin_top' => 'required|integer|min:0|max:200',
            'result_date' => 'nullable|date_format:Y-m-d',
        ], [
            'term.required_if' => trans('validation.required', ['attribute' => trans('exam.term.term')]),
            'exam.required_if' => trans('validation.required', ['attribute' => trans('exam.exam')]),
        ]);

        $request->merge([
            'result_date' => empty($request->result_date) ? today()->toDateString() : $request->result_date,
        ]);

        $batch = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'batch');

        $exam = $request->exam ? Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->whereUuid($request->exam)
            ->getOrFail(trans('exam.exam'), 'exam') : null;

        if ($exam && auth()->user()->is_student_or_guardian && ! $exam->getMeta('publish_marksheet')) {
            throw ValidationException::withMessages(['message' => trans('exam.marksheet.not_published')]);
        }

        $queryStudents = Str::toArray($request->query('students'));

        $params = $request->all();

        $params['attendance_till_last_exam_date'] = true;
        $params['student_wise_attendance_working_days'] = false;
        $params['assessment_wise_result'] = true; // if true, then fail in any assessment will make the result fail
        $params['show_overall_total'] = $request->boolean('show_overall_total');
        $params['show_overall_grade'] = $request->boolean('show_overall_grade');
        $params['show_summary_report'] = $request->boolean('show_summary_report');
        $params['sort_summary_report_by_rank'] = $request->boolean('sort_summary_report_by_rank');

        if (count($queryStudents)) {
            $params['students'] = $queryStudents;
            $params['select_all'] = false;
        } else {
            $params['select_all'] = true;
        }

        if ($request->query('show_all_student')) {
            $params['status'] = 'all';
        }

        if ($request->boolean('show_course_wise')) {
            $params['batch'] = $batch->course->batches->pluck('uuid')->all();
        }

        $params['show_detail'] = true;

        $students = (new FetchBatchWiseStudent)->execute($params);

        if (in_array($request->type, ['cumulative', 'term_wise'])) {
            return (new CumulativeMarksheetService)->getData($batch, $students, [
                ...$params,
                'show_summary_report' => $request->boolean('show_summary_report'),
                'sort_summary_report_by_rank' => $request->boolean('sort_summary_report_by_rank'),
            ]);
        }

        if (in_array($request->type, ['exam_wise_credit_based'])) {
            return (new CreditBasedMarksheetService)->getData($batch, $students, [
                ...$params,
                'show_summary_report' => $request->boolean('show_summary_report'),
                'sort_summary_report_by_rank' => $request->boolean('sort_summary_report_by_rank'),
            ]);
        }

        $schedule = Schedule::query()
            ->with(['grade', 'assessment', 'records.subject'])
            ->whereExamId($exam->id)
            ->whereBatchId($batch->id)
            ->getOrFail(trans('exam.schedule.schedule'));

        $scheduleAssessment = collect($schedule->assessment->records ?? []);
        $grade = $schedule->grade;

        $failGrades = collect($grade->records)->filter(function ($record) {
            return Arr::get($record, 'is_fail_grade');
        })->pluck('code')->all();

        $lastExamDate = $schedule->records->where('config.has_exam', true)->sortByDesc('date.value')->first()?->date;

        $healthRecords = HealthRecord::query()
            ->whereModelType('Student')
            ->whereIn('model_id', $students->pluck('id')->all())
            ->where('date', '<=', $lastExamDate->value)
            ->orderBy('date', 'desc')
            ->get();

        $comments = Arr::get($schedule->details, 'comments', []);

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

        $examAttendances = collect(Arr::get($schedule->details, 'attendances', []));

        $totalWorkingDays = 0;
        if (! Arr::get($params, 'student_wise_attendance_working_days')) {
            $totalWorkingDays = $overallAttendances->count();
            if (! empty(Arr::get($schedule->details, 'total_working_days'))) {
                $totalWorkingDays = Arr::get($schedule->details, 'total_working_days');
            }
        }

        $comments = Arr::get($schedule->details, 'comments', []);

        $subjectRecords = SubjectRecord::query()
            ->where(function ($q) use ($batch) {
                $q->where('course_id', $batch->course_id)
                    ->orWhere('batch_id', $batch->id);
            })
            ->whereIn('subject_id', $schedule->records->pluck('subject_id'))
            ->get();

        $subjects = [];

        // Preparing subjects
        foreach ($schedule->records as $examRecord) {
            $hasExam = $examRecord->getConfig('has_exam');

            if (! $hasExam) {
                continue;
            }

            $recordMarks = $examRecord->marks ?? [];

            $notApplicableStudents = $examRecord->getConfig('not_applicable_students', []);

            $assessments = collect($examRecord->getConfig('assessments', []))
                ->map(function ($assessment) use ($scheduleAssessment) {
                    $code = Arr::get($assessment, 'code');

                    return [
                        'name' => Arr::get($scheduleAssessment->firstWhere('code', $code), 'name'),
                        'code' => $code,
                        'max_mark' => Arr::get($assessment, 'max_mark', 0),
                    ];
                });

            $subjectRecord = $subjectRecords->firstWhere('subject_id', $examRecord->subject_id);

            $subjects[] = [
                'id' => $examRecord->subject_id,
                'name' => $examRecord->subject->name,
                'shortcode' => $examRecord->subject->shortcode,
                'assessments' => $assessments,
                'total' => $assessments->sum('max_mark'),
                'marks' => $recordMarks,
                'not_applicable_students' => $notApplicableStudents,
                'position' => $examRecord->subject->position ?? 0,
                'has_grading' => (bool) $subjectRecord?->has_grading,
            ];
        }

        $subjects = collect($subjects)->sortBy('position')->values()->all();

        $mainSubjects = collect($subjects)->filter(function ($subject) {
            return ! $subject['has_grading'];
        })->toArray();

        $gradingSubjects = collect($subjects)->filter(function ($subject) {
            return $subject['has_grading'];
        })->toArray();

        // Preparing marks
        foreach ($subjects as $subject) {
            $subjectId = Arr::get($subject, 'id');
            $assessments = Arr::get($subject, 'assessments', []);
            $subjectMarks = Arr::get($subject, 'marks', []);

            foreach ($assessments as $assessment) {
                $assessmentMarks = collect($subjectMarks)->firstWhere('code', $assessment['code']);

                $marks[] = [
                    'subject_id' => $subjectId,
                    'assessment_code' => $assessment['code'],
                    'max_mark' => $assessment['max_mark'],
                    'marks' => $assessmentMarks['marks'] ?? [],
                ];
            }
        }

        $marks = collect($marks);

        // Preparing header
        $header = [];

        if ($request->boolean('show_sno')) {
            array_push($header, ['key' => 'sno', 'class' => 'font-weight-bold', 'label' => trans('general.sno')]);
        }
        array_push($header, ['key' => 'subject', 'class' => 'font-weight-bold', 'label' => trans('academic.subject.subject')]);

        $examAssessments = $scheduleAssessment;
        if (count($scheduleAssessment) > 1 && $request->boolean('cumulative_assessment')) {
            $examAssessments = $scheduleAssessment->take(1);
        }

        foreach ($examAssessments as $assessment) {
            $maxMarkLabel = trans('exam.max_mark');
            $obtainedMarkLabel = trans('exam.obtained_mark');

            if (count($examAssessments) > 1 && ! $request->boolean('cumulative_assessment')) {
                $maxMarkLabel = $assessment['name'].' ('.trans('exam.max_mark_short').')';
                $obtainedMarkLabel = $assessment['name'].' ('.trans('exam.obtained_mark_short').')';
            }

            $header[] = [
                'key' => "assessment_code{$assessment['code']}_max_mark",
                'class' => 'font-weight-bold text-center',
                'label' => $maxMarkLabel,
            ];

            $header[] = [
                'key' => "assessment_code{$assessment['code']}",
                'class' => 'font-weight-bold text-center',
                'label' => $obtainedMarkLabel,
            ];
        }

        if (count($examAssessments) > 1 && ! $request->boolean('cumulative_assessment')) {
            array_push($header, ['key' => 'total', 'class' => 'font-weight-bold text-center', 'label' => trans('exam.total')]);
        }

        array_push($header, ['key' => 'grade', 'class' => 'font-weight-bold text-center', 'label' => trans('exam.result_grade')]);

        $observation = Observation::query()
            ->with('grade')
            ->whereIn('id', $schedule->pluck('observation_id')->all())
            ->first();

        $observationParameterRecords = [];
        foreach ($observation->records ?? [] as $observationRecord) {
            $observationParameterRecords[] = [
                'name' => Arr::get($observationRecord, 'name'),
                'code' => Arr::get($observationRecord, 'code'),
                'max_mark' => Arr::get($observationRecord, 'max_mark'),
            ];
        }

        $observationMarks = collect(Arr::get($schedule->details, 'observation_marks', []));

        $summaryHeader = [
            [
                'label' => trans('student.props.name'),
                'class' => 'font-weight-bold',
            ],
            [
                'label' => trans('exam.total'),
                'class' => 'font-weight-bold',
            ],
            [
                'label' => trans('exam.percentage'),
                'class' => 'font-weight-bold',
            ],
            [
                'label' => trans('exam.grade.grade'),
                'class' => 'font-weight-bold',
            ],
        ];

        $summaryRows = [];

        // Preparing students
        foreach ($students as $student) {
            $rows = [];
            $gradingRows = [];
            $observationRows = [];

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

            $student->health_record = $healthRecords->firstWhere('model_id', $student->id)?->details ?? [];

            $i = 0;
            $maxTotal = 0;
            $obtainedTotal = 0;
            $studentMarks = [];
            $studentSubjectGrades = [];
            $studentAssessmentGrades = [];
            foreach ($mainSubjects as $index => $subject) {
                $row = [];

                $notApplicableStudents = $subject['not_applicable_students'] ?? [];

                if (in_array($student->uuid, $notApplicableStudents)) {
                    continue;
                }

                $i++;

                if ($request->boolean('show_sno')) {
                    array_push($row, ['key' => "subject_index_{$subject['id']}", 'label' => $i]);
                }
                array_push($row, ['key' => "subject_name_{$subject['id']}", 'label' => $subject['name']]);

                $assessments = $subject['assessments'];

                $subjectTotal = 0;
                $subjectMaxMark = 0;
                foreach ($assessments as $assessment) {
                    $assessmentMark = $marks
                        ->where('subject_id', $subject['id'])
                        ->where('assessment_code', $assessment['code'])
                        ->first();

                    $maxTotal += $assessment['max_mark'];
                    $subjectMaxMark += $assessment['max_mark'];

                    $mark = collect($assessmentMark['marks'] ?? []);

                    $studentMark = $mark->firstWhere('uuid', $student->uuid);
                    $obtainedMark = $studentMark['obtained_mark'] ?? '';

                    $studentMarks[] = [
                        'subject_id' => $subject['id'],
                        'assessment_code' => $assessment['code'],
                        'max_mark' => $assessment['max_mark'],
                        'obtained_mark' => $obtainedMark,
                    ];

                    $assessmentGrade = '';
                    if (is_numeric($obtainedMark)) {
                        $obtainedTotal += $obtainedMark;
                        $subjectTotal += $obtainedMark;
                        $assessmentGrade = $this->getGrade($grade, $assessment['max_mark'], $obtainedMark);
                    }

                    $studentAssessmentGrades[] = $assessmentGrade;

                    $failedInAssessment = false;
                    if (Arr::get($params, 'assessment_wise_result') && in_array($assessmentGrade, $failGrades)) {
                        $failedInAssessment = true;
                    }

                    if (
                        count($assessments) == 1 ||
                        (count($assessments) > 1 && ! $request->boolean('cumulative_assessment'))
                    ) {
                        array_push($row, ['key' => "subject_{$subject['id']}_assessment_{$assessment['code']}_max_mark", 'class' => 'text-center', 'label' => $assessment['max_mark'] > 0 ? $assessment['max_mark'] : '']);

                        array_push($row, ['key' => "subject_{$subject['id']}_assessment_{$assessment['code']}", 'class' => 'text-center', 'span-style' => $failedInAssessment ? 'circular-border' : '', 'label' => $obtainedMark]);
                    }
                }

                if (count($assessments) > 1 && $request->boolean('cumulative_assessment')) {
                    array_push($row, ['key' => "subject_{$subject['id']}_assessment_{$assessments[0]['code']}_max_mark", 'class' => 'text-center', 'label' => $subjectMaxMark]);

                    array_push($row, ['key' => "subject_{$subject['id']}_assessment_{$assessments[0]['code']}", 'class' => 'text-center', 'span-style' => $failedInAssessment ? 'circular-border' : '', 'label' => $subjectTotal]);
                }

                if (count($assessments) > 1 && ! $request->boolean('cumulative_assessment')) {
                    array_push($row, ['key' => "subject_total_{$subject['id']}", 'class' => 'text-center', 'label' => $subjectTotal]);
                }

                $subjectGrade = $this->getGrade($grade, $subjectMaxMark, $subjectTotal);

                $studentSubjectGrades[] = [
                    'subject_id' => $subject['id'],
                    'grade' => $subjectGrade,
                ];

                array_push($row, ['key' => "subject_grade_{$subject['id']}", 'class' => 'text-center', 'label' => $subjectGrade]);

                $rows[] = $row;
            }

            $footerRow = [];

            array_push($footerRow, ['key' => 'total', 'class' => 'font-weight-bold', 'label' => trans('exam.total'), 'colspan' => $request->boolean('show_sno') ? 2 : 1]);

            if (count($scheduleAssessment) == 1 || (count($scheduleAssessment) > 1 && ! $request->boolean('cumulative_assessment'))) {
                foreach ($scheduleAssessment as $assessment) {
                    $studentMaxTotal = collect($studentMarks)
                        ->where('assessment_code', $assessment['code'])
                        ->filter(function ($mark) {
                            return is_numeric($mark['max_mark']);
                        })
                        ->sum('max_mark');

                    $studentObtainedTotal = collect($studentMarks)
                        ->where('assessment_code', $assessment['code'])
                        ->filter(function ($mark) {
                            return is_numeric($mark['obtained_mark']);
                        })
                        ->sum('obtained_mark');

                    array_push($footerRow, ['key' => "assessment_{$assessment['code']}_total", 'class' => 'text-center font-weight-bold', 'label' => $studentMaxTotal]);

                    array_push($footerRow, ['key' => "obtained_{$assessment['code']}_total", 'class' => 'text-center font-weight-bold', 'label' => $studentObtainedTotal]);
                }
            }

            $studentMaxTotal = collect($studentMarks)
                ->filter(function ($mark) {
                    return is_numeric($mark['max_mark']);
                })
                ->sum('max_mark');

            $studentGrandTotal = collect($studentMarks)
                ->filter(function ($mark) {
                    return is_numeric($mark['obtained_mark']);
                })
                ->sum('obtained_mark');

            if (count($scheduleAssessment) > 1 && $request->boolean('cumulative_assessment')) {
                array_push($footerRow, ['key' => "assessment_{$assessments[0]['code']}_total", 'class' => 'text-center font-weight-bold', 'label' => $studentMaxTotal]);

                array_push($footerRow, ['key' => "obtained_{$assessments[0]['code']}_total", 'class' => 'text-center font-weight-bold', 'label' => $studentGrandTotal]);
            }

            if (count($scheduleAssessment) > 1 && ! $request->boolean('cumulative_assessment')) {
                array_push($footerRow, ['key' => "total_{$student->uuid}", 'class' => 'text-center font-weight-bold', 'label' => $studentGrandTotal]);
            }

            $studentGrade = $this->getGrade($grade, $studentMaxTotal, $studentGrandTotal);

            array_push($footerRow, ['key' => "grade_{$student->uuid}", 'class' => 'text-center font-weight-bold', 'label' => $studentGrade]);

            $student->footer = $footerRow;

            $percentage = $studentMaxTotal > 0 ? round(($studentGrandTotal / $studentMaxTotal) * 100, 2) : 0;

            $student->max_total = $studentMaxTotal;
            $student->total = $studentGrandTotal;
            $student->grade = $studentGrade;
            $student->percentage = $percentage;

            // custom result logic

            $result = collect($studentSubjectGrades)->filter(function ($subjectGrade) use ($failGrades) {
                return in_array($subjectGrade['grade'], $failGrades);
            })->count() > 0 ? trans('exam.results.fail') : trans('exam.results.pass');

            if ($result == trans('exam.results.pass') && Arr::get($params, 'assessment_wise_result') && collect($studentAssessmentGrades)->filter(function ($assessmentGrade) use ($failGrades) {
                return in_array($assessmentGrade, $failGrades);
            })->count() > 0) {
                $result = trans('exam.results.fail');
            }

            // custom result logic

            $student->result = $result;

            foreach ($gradingSubjects as $index => $subject) {
                $row = [];

                $notApplicableStudents = $subject['not_applicable_students'] ?? [];

                if (in_array($student->uuid, $notApplicableStudents)) {
                    continue;
                }

                $i++;

                array_push($row, ['key' => "subject_name_{$subject['id']}", 'label' => $subject['name']]);

                $assessments = $subject['assessments'];

                $subjectTotal = 0;
                $subjectMaxMark = 0;
                foreach ($assessments as $assessment) {
                    $assessmentMark = $marks
                        ->where('subject_id', $subject['id'])
                        ->where('assessment_code', $assessment['code'])
                        ->first();

                    $maxTotal += $assessment['max_mark'];
                    $subjectMaxMark += $assessment['max_mark'];

                    $mark = collect($assessmentMark['marks'] ?? []);

                    $studentMark = $mark->firstWhere('uuid', $student->uuid);
                    $obtainedMark = $studentMark['obtained_mark'] ?? '';

                    if (is_numeric($obtainedMark)) {
                        $obtainedTotal += $obtainedMark;
                        $subjectTotal += $obtainedMark;
                    }
                }

                $subjectGrade = $this->getGrade($grade, $subjectMaxMark, $subjectTotal);

                array_push($row, ['key' => "subject_grade_{$subject['id']}", 'class' => 'text-center', 'label' => $subjectGrade]);

                $gradingRows[] = $row;
            }

            foreach ($observationParameterRecords as $index => $observationParameterRecord) {
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

            $student->observation_marks = $observationRows;

            $student->max_total = $maxTotal;
            $student->obtained_total = $obtainedTotal;
            $student->marks = $rows;
            $student->grading_marks = $gradingRows;

            $comment = collect($comments)->firstWhere('uuid', $student->uuid);

            $student->comment = [
                'result' => Arr::get($comment, 'result') ?: $student->result,
                'comment' => Arr::get($comment, 'comment') ?: Arr::get(collect($grade->records)->firstWhere('code', $student->grade), 'label'),
            ];

            array_push($summaryRows, [
                [
                    'label' => $student->name,
                ],
                [
                    'label' => $student->total,
                ],
                [
                    'label' => $student->percentage,
                ],
                [
                    'label' => $student->grade,
                ],
            ]);
        }

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

        $boxWidth = match ((int) $request->query('column')) {
            1 => '100%',
            2 => '48%',
            3 => '33%',
            default => '100%',
        };

        $layout = [
            'column' => $request->query('column', 1),
            'margin_top' => $request->query('margin_top', 0),
            'box_width' => $boxWidth,
            'show_print_date_time' => (bool) Arr::get($exam->config_detail, 'show_print_date_time'),
            'show_watermark' => (bool) Arr::get($exam->config_detail, 'show_watermark'),
            'signatory1' => Arr::get($exam->config_detail, 'signatory1'),
            'signatory2' => Arr::get($exam->config_detail, 'signatory2'),
            'signatory3' => Arr::get($exam->config_detail, 'signatory3'),
            'signatory4' => Arr::get($exam->config_detail, 'signatory4'),
        ];

        if (Arr::get($params, 'show_summary_report')) {
            array_shift($titles);

            if (Arr::get($params, 'sort_summary_report_by_rank')) {
                $summaryRows = collect($summaryRows)->sortByDesc(function ($row) {
                    return $row[1]['label'];
                })->values()->all();
            }

            return view()->first([config('config.print.custom_path').'exam.summary-report', 'print.exam.summary-report'], compact('summaryHeader', 'summaryRows', 'titles', 'period', 'layout'))->render();
        }

        if ($request->type == 'exam_wise') {
            return view()->first([config('config.print.custom_path').'exam.exam-wise-marksheet', 'print.exam.exam-wise-marksheet'], compact('titles', 'students', 'header', 'grade', 'period', 'layout', 'params'))->render();
        }

        return view()->first([config('config.print.custom_path').'exam.marksheet', 'print.exam.marksheet'], compact('titles', 'students', 'header', 'period', 'layout', 'params'))->render();
    }
}
