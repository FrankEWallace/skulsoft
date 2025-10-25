<?php

namespace App\Services\Exam;

use App\Models\Academic\Batch;
use App\Models\Academic\Period;
use App\Models\Academic\Subject;
use App\Models\Exam\Exam;
use App\Models\Exam\Schedule;
use App\Models\Student\SubjectWiseStudent;
use App\Support\HasGrade;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CreditBasedMarksheetService
{
    use HasGrade;

    public function getData(Batch $batch, Collection $students, array $params)
    {
        $params['show_total_column'] = true;
        $params['show_percentage_column'] = true;
        $params['show_grade_column'] = true;
        $params['show_grade_detail'] = true;
        $params['show_s_no'] = false;
        $params['show_assessment_wise_report'] = false;
        $params['hide_term_row'] = false;
        $params['multi_column_heading_observation_record'] = false;

        if ($params['show_assessment_wise_report']) {
            $params['show_total_column'] = false;
            $params['show_percentage_column'] = false;
            $params['show_grade_column'] = false;
        }

        $exam = Exam::query()
            ->byPeriod()
            ->where('uuid', Arr::get($params, 'exam'))
            ->firstOrFail();

        $schedule = Schedule::query()
            ->with('records.subject')
            ->whereExamId($exam->id)
            ->whereBatchId($batch->id)
            ->firstOrFail();

        $examGrade = $schedule->grade;
        $failGrades = collect($examGrade->records)->where('is_fail_grade', true)->pluck('code')->all();

        $subjects = Subject::query()
            ->withSubjectRecord($batch->id, $batch->course_id)
            ->orderBy('subjects.position', 'asc')
            ->get();

        $mainSubjects = $subjects->where('has_grading', 0);
        $gradingSubjects = $subjects->where('has_grading', 1);

        $scheduleAssessmentRecords = collect($schedule->assessment->records ?? []);
        foreach ($schedule->records as $record) {

            $subject = $subjects->firstWhere('id', $record->subject_id);

            if (! $subject) {
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

        $subjectWiseStudents = SubjectWiseStudent::query()
            ->whereBatchId($batch->id)
            ->whereIn('student_id', $students->pluck('id')->all())
            ->get();

        foreach ($students as $student) {
            $rows = [];

            $row = [];
            array_push($row, ['label' => trans('academic.course.props.code'),
                'align' => 'center',
                'bold' => true, ]);
            array_push($row, ['label' => trans('academic.course.props.title'),
                'align' => 'center',
                'bold' => true, ]);
            array_push($row, ['label' => trans('academic.subject.props.credit'),
                'align' => 'center',
                'bold' => true, ]);
            array_push($row, ['label' => trans('exam.grade.grade'),
                'align' => 'center',
                'bold' => true, ]);
            array_push($row, ['label' => trans('exam.grade.props.point'),
                'align' => 'center',
                'bold' => true, ]);
            array_push($row, ['label' => trans('exam.grade.props.credit_point'),
                'align' => 'center',
                'bold' => true, ]);
            $rows[] = $row;

            $totalCredit = 0;
            $totalObtainedCredit = 0;
            $totalGradePoint = 0;
            $failedGrade = 0;
            $totalAbsentCount = 0;
            $failedGradeSubjects = [];
            $absentSubjects = [];
            foreach ($examRecords as $examRecord) {

                if ($examRecord['is_elective'] && ! $subjectWiseStudents->where('student_id', $student->id)->firstWhere('subject_id', $examRecord['subject_id'])) {
                    continue;
                }

                $row = [];
                $subject = $subjects->firstWhere('id', $examRecord['subject_id']);

                array_push($row, ['label' => $subject->code]);
                array_push($row, ['label' => $subject->name]);
                array_push($row, ['label' => $subject->credit,
                    'align' => 'center', ]);

                $totalCredit += $subject->credit;

                $assessments = $examRecord['assessments'];

                $isAbsent = false;
                $subjectMaxMarks = 0;
                $subjectTotal = 0;
                $absentCount = 0;
                $markNotRecorded = 0;
                foreach ($assessments as $assessment) {
                    $assessmentMark = collect(Arr::get($examRecord, 'marks', []))
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

                // if absent in all assessments then consider as absent
                if ($absentCount == count($assessments)) {
                    $absentSubjects[] = $subject;
                    $isAbsent = true;
                }

                // if absent in any assessment then consider as absent
                // if ($absentCount) {
                //     $absentSubjects[] = $subject;
                //     $isAbsent = true;
                // }

                $subjectGrade = $this->getGrade($schedule->grade, $subjectMaxMarks, $subjectTotal, 'grade');

                if ($isAbsent) {
                    $subjectGrade = ['code' => 'Ab', 'value' => 0];
                    $totalAbsentCount++;
                }

                array_push($row, ['label' => Arr::get($subjectGrade, 'code'),
                    'align' => 'center', ]);

                if (in_array(Arr::get($subjectGrade, 'code'), $failGrades)) {
                    $failedGradeSubjects[] = $subject;
                    $failedGrade++;
                }

                if (! in_array(Arr::get($subjectGrade, 'code'), $failGrades) && ! $isAbsent) {
                    $totalObtainedCredit += $subject->credit;
                }

                array_push($row, ['label' => (float) Arr::get($subjectGrade, 'value'),
                    'align' => 'center', ]);

                if (is_numeric(Arr::get($subjectGrade, 'value'))) {
                    $creditPoint = $subject->credit * Arr::get($subjectGrade, 'value');
                } else {
                    $creditPoint = 0;
                }

                $totalGradePoint += $creditPoint;

                array_push($row, ['label' => $creditPoint,
                    'align' => 'center', ]);

                $rows[] = $row;
            }

            $row = [
                [
                    'label' => trans('exam.total'), 'colspan' => 2, 'bold' => true,
                ],
                [
                    'label' => $totalCredit, 'align' => 'center', 'bold' => true,
                ],
                [
                    'label' => '', 'bold' => true,
                ],
                [
                    'label' => '', 'bold' => true,
                ],
                [
                    'label' => $totalGradePoint, 'align' => 'center', 'bold' => true,
                ],
            ];

            $rows[] = $row;

            $gpa = $totalCredit ? round($totalGradePoint / $totalCredit, 2) : '';

            $row = [
                [
                    'label' => trans('exam.gpa'), 'colspan' => 5, 'bold' => true,
                ],
                [
                    'label' => $gpa, 'align' => 'center', 'bold' => true,
                ],
            ];

            $rows[] = $row;

            $student->rows = $rows;

            $result = $failedGrade ? trans('exam.results.fail') : trans('exam.results.pass');

            // custom result logic

            // end custom result logic

            $summaryRows = [
                [
                    [
                        'label' => trans('exam.marksheet.total_credit'),
                        'bold' => true,
                    ],
                    [
                        'label' => $totalCredit,
                        'bold' => true,
                    ],
                ],
                [
                    [
                        'label' => trans('exam.marksheet.obtained_credit'),
                        'bold' => true,
                    ],
                    [
                        'label' => $totalObtainedCredit,
                        'bold' => true,
                    ],
                ],
                [
                    [
                        'label' => trans('exam.gpa'),
                        'bold' => true,
                    ],
                    [
                        'label' => $gpa,
                        'bold' => true,
                    ],
                ],
                [
                    [
                        'label' => trans('exam.result'),
                        'bold' => true,
                    ],
                    [
                        'label' => $result,
                        'bold' => true,
                    ],
                ],
            ];

            $student->summary = [
                'total_credit' => $totalCredit,
                'total_obtained_credit' => $totalObtainedCredit,
                'total_grade_point' => $totalGradePoint,
                'gpa' => $gpa,
                'result' => $result,
            ];
            $student->gpa = $gpa;

            $student->summaryRows = $summaryRows;
        }

        if (Arr::get($params, 'show_summary_report')) {
            if (Arr::get($params, 'sort_summary_report_by_rank')) {
                $students = $students->sortByDesc(function ($row) {
                    return $row->gpa;
                });
            }

            return view()->first([config('config.print.custom_path').'exam.credit-based-summary-report', 'print.exam.credit-based-summary-report'], [
                'students' => $students,
                'params' => $params,
                ...$this->getMeta($exam, $batch, $params),
            ])->render();
        }

        return view()->first([config('config.print.custom_path').'exam.exam-wise-credit-based-marksheet', 'print.exam.exam-wise-credit-based-marksheet'], [
            'students' => $students,
            'params' => $params,
            ...$this->getMeta($exam, $batch, $params),
        ])->render();
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
