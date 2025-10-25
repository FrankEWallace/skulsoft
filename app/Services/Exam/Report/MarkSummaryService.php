<?php

namespace App\Services\Exam\Report;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Enums\Exam\AssessmentAttempt;
use App\Http\Resources\Exam\ExamResource;
use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectRecord;
use App\Models\Exam\Exam;
use App\Models\Exam\Grade;
use App\Models\Exam\Schedule;
use App\Models\Incharge;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;
use App\Support\HasGrade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class MarkSummaryService
{
    use HasGrade;

    public function preRequisite(): array
    {
        $exams = ExamResource::collection(Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->get());

        $attempts = AssessmentAttempt::getOptions();

        return compact('exams', 'attempts');
    }

    public function fetchReport(Request $request)
    {
        $request->validate([
            'exam' => 'required|uuid',
            'attempt' => ['required', new Enum(AssessmentAttempt::class)],
            'batch' => 'required|uuid',
            'title' => 'string|nullable|max:255',
            'signatory_1' => 'string|nullable|max:255',
            'signatory_2' => 'string|nullable|max:255',
            'signatory_3' => 'string|nullable|max:255',
            'signatory_4' => 'string|nullable|max:255',
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

        $schedule = Schedule::query()
            ->with(['grade', 'assessment', 'records.subject'])
            ->whereExamId($exam->id)
            ->whereBatchId($batch->id)
            ->where('attempt', $request->attempt)
            ->getOrFail(trans('exam.schedule.schedule'));

        $subjectRecords = SubjectRecord::query()
            ->where(function ($q) use ($batch) {
                $q->where('course_id', $batch->course_id)
                    ->orWhere('batch_id', $batch->id);
            })
            ->whereIn('subject_id', $schedule->records->pluck('subject_id'))
            ->get();

        $subjectWiseStudents = SubjectWiseStudent::query()
            ->whereBatchId($batch->id)
            ->get();

        $params = [
            'subject_wise_students' => $subjectWiseStudents,
        ];

        $inputSubjects = Subject::query()
            ->byPeriod()
            ->whereIn('uuid', Str::toArray($request->query('subjects')))
            ->get();

        $request->merge([
            'select_all' => true,
        ]);

        $params = $request->all();

        if ($request->boolean('show_all_student')) {
            $params['status'] = 'all';
        }

        $students = (new FetchBatchWiseStudent)->execute($params);

        $scheduleAssessment = collect($schedule->assessment->records ?? []);
        $grade = $schedule->grade;

        $failGrades = collect($grade->records)->filter(function ($record) {
            return Arr::get($record, 'is_fail_grade');
        })->pluck('code')->all();

        $params['fail_grades'] = $failGrades;

        $rows = [];
        $header = [];
        $subHeader = [];
        $marks = [];
        array_push($header, ['key' => 'sno', 'rowspan' => 2, 'class' => 'font-weight-bold', 'label' => trans('general.sno')]);
        array_push($header, ['key' => 'roll_number', 'rowspan' => 2, 'class' => 'font-weight-bold', 'label' => trans('student.roll_number.roll_number')]);
        array_push($header, ['key' => 'name', 'rowspan' => 2, 'class' => 'font-weight-bold', 'label' => trans('student.props.name')]);

        $subjects = [];
        $maxTotal = 0;
        foreach ($schedule->records as $examRecord) {
            if ($inputSubjects->count() && ! in_array($examRecord->subject_id, $inputSubjects->pluck('id')->toArray())) {
                continue;
            }

            $hasExam = $examRecord->getConfig('has_exam');

            if (! $hasExam) {
                continue;
            }

            $subjectRecord = $subjectRecords->firstWhere('subject_id', $examRecord->subject_id);

            $recordMarks = $examRecord->marks ?? [];
            $notApplicableStudents = $examRecord->getConfig('not_applicable_students', []);

            $assessments = collect($examRecord->getConfig('assessments', []))
                ->filter(function ($assessment) {
                    return Arr::get($assessment, 'max_mark', 0) > 0;
                })
                ->map(function ($assessment) use ($scheduleAssessment) {
                    $code = Arr::get($assessment, 'code');

                    return [
                        'name' => Arr::get($scheduleAssessment->firstWhere('code', $code), 'name'),
                        'code' => $code,
                        'max_mark' => Arr::get($assessment, 'max_mark', 0),
                    ];
                });

            $subjects[] = [
                'id' => $examRecord->subject_id,
                'name' => $examRecord->subject->name,
                'shortcode' => $examRecord->subject->shortcode,
                'assessments' => $assessments,
                'position' => $examRecord->subject->position,
                'total' => $assessments->sum('max_mark'),
                'marks' => $recordMarks,
                'not_applicable_students' => $notApplicableStudents,
                'is_elective' => (bool) $subjectRecord?->is_elective,
                'has_grading' => (bool) $subjectRecord?->has_grading,
            ];
        }

        $subjects = collect($subjects)->sortBy('position')->toArray();

        $mainSubjects = collect($subjects)->filter(function ($subject) {
            return ! $subject['has_grading'];
        })->toArray();

        $gradingSubjects = collect($subjects)->filter(function ($subject) {
            return $subject['has_grading'];
        })->toArray();

        $gradingSubjectAssessmentCount = collect($gradingSubjects)->sum(function ($subject) {
            return count($subject['assessments']);
        });

        $maxTotal = collect($mainSubjects)->sum('total');

        [$header, $subHeader, $marks] = $this->updateSubjects($header, $subHeader, $marks, $mainSubjects);

        array_push($header, ['key' => 'total', 'class' => 'text-center font-weight-bold', 'rowspan' => 2, 'label' => trans('exam.total')]);
        array_push($header, ['key' => 'percent', 'class' => 'text-center font-weight-bold', 'rowspan' => 2, 'label' => trans('exam.percent')]);
        array_push($header, ['key' => 'grade', 'class' => 'text-center font-weight-bold', 'rowspan' => 2, 'label' => trans('exam.result_grade')]);

        [$header, $subHeader, $marks] = $this->updateSubjects($header, $subHeader, $marks, $gradingSubjects);

        $marks = collect($marks);
        $i = 0;
        foreach ($students as $index => $student) {
            $studentTotal = 0;
            $row = [];

            $row = $this->getStudentMarks($student, $grade, $marks, $mainSubjects, $row, $params);

            if (collect($row)->filter(function ($row) {
                return Arr::get($row, 'type') == 'marks' && Arr::get($row, 'label') == '';
            })->count() == count($row)) {
                continue;
            }

            $i++;

            array_unshift($row, ['key' => 'name', 'label' => $student->name.' ('.$student->code_number.')']);
            array_unshift($row, ['key' => 'roll_number', 'label' => $student->roll_number]);
            array_unshift($row, ['key' => 'sno', 'label' => $i]);

            $hasFailGrade = collect($row)->filter(function ($row) use ($failGrades) {
                return in_array(Arr::get($row, 'grade'), $failGrades);
            })->count();

            $studentMaxMarkTotal = collect($row)->sum('max_mark');
            $studentTotal = collect($row)->sum('numeric_mark');

            $row[] = ['key' => 'total', 'class' => 'text-center', 'label' => $studentTotal];

            $studentPercent = $studentMaxMarkTotal > 0 ? round(($studentTotal / $studentMaxMarkTotal) * 100, 2) : 0;

            $row[] = ['key' => 'percent', 'class' => 'text-center', 'label' => $studentPercent];

            $studentGrade = $this->getGrade($grade, $studentMaxMarkTotal, $studentTotal);

            $row[] = ['key' => 'grade', 'class' => 'text-center', 'label' => $studentGrade, 'text-style' => $hasFailGrade ? 'circular-border' : ''];

            $row = $this->getStudentMarks($student, $grade, $marks, $gradingSubjects, $row);

            $rows[] = $row;
        }

        $highestRow[] = ['key' => 'highest', 'class' => 'font-weight-bold', 'label' => trans('exam.highest_mark'), 'colspan' => 3];
        $lowestRow[] = ['key' => 'lowest', 'class' => 'font-weight-bold', 'label' => trans('exam.lowest_mark'), 'colspan' => 3];
        $averageRow[] = ['key' => 'average', 'class' => 'font-weight-bold', 'label' => trans('exam.average_mark'), 'colspan' => 3];
        $absentRow[] = ['key' => 'average', 'class' => 'font-weight-bold', 'label' => trans('exam.total_absent'), 'colspan' => 3];
        $passRow[] = ['key' => 'pass', 'class' => 'font-weight-bold', 'label' => trans('global.total', ['attribute' => trans('exam.results.pass')]), 'colspan' => 3];
        $failRow[] = ['key' => 'fail', 'class' => 'font-weight-bold', 'label' => trans('global.total', ['attribute' => trans('exam.results.fail')]), 'colspan' => 3];

        foreach ($mainSubjects as $subject) {
            foreach ($subject['assessments'] as $assessment) {

                $marks = [];
                foreach ($rows as $row) {
                    $marks[] = collect($row)->filter(function ($row) use ($subject, $assessment) {
                        return Arr::get($row, 'subject_id') == $subject['id'] && Arr::get($row, 'assessment_code') == $assessment['code'];
                    })->first();
                }

                $highestRow[] = ['key' => "highest{$subject['id']}_{$assessment['code']}", 'class' => 'text-center font-weight-bold', 'label' => collect($marks)->max('numeric_mark')];

                $lowestRow[] = ['key' => "lowest{$subject['id']}_{$assessment['code']}", 'class' => 'text-center font-weight-bold', 'label' => collect($marks)->filter(function ($mark) {
                    return is_numeric(Arr::get($mark, 'label'));
                })->min('numeric_mark')];

                $averageRow[] = ['key' => "average{$subject['id']}_{$assessment['code']}", 'class' => 'text-center font-weight-bold', 'label' => round(collect($marks)
                    ->filter(function ($mark) {
                        return Arr::get($mark, 'label') != 'NA';
                    })->average('numeric_mark'), 2)];

                $absentRow[] = ['key' => "absent{$subject['id']}_{$assessment['code']}", 'class' => 'text-center font-weight-bold', 'label' => collect($marks)->whereIn('label', ['A', 'a', 'Ab', 'ab'])->count()];

                $failRow[] = ['key' => "fail{$subject['id']}_{$assessment['code']}", 'class' => 'text-center font-weight-bold', 'label' => collect($marks)->filter(function ($mark) use ($failGrades) {
                    return in_array(Arr::get($mark, 'grade'), $failGrades);
                })->count()];

                $passRow[] = ['key' => "pass{$subject['id']}_{$assessment['code']}", 'class' => 'text-center font-weight-bold', 'label' => collect($marks)->filter(function ($mark) use ($failGrades) {
                    return ! in_array(Arr::get($mark, 'label'), ['NA', 'A']) && ! in_array(Arr::get($mark, 'grade'), $failGrades);
                })->count()];
            }
        }

        $highestRow[] = ['key' => 'extra', 'label' => '', 'colspan' => 3 + $gradingSubjectAssessmentCount];
        $lowestRow[] = ['key' => 'extra', 'label' => '', 'colspan' => 3 + $gradingSubjectAssessmentCount];
        $averageRow[] = ['key' => 'extra', 'label' => '', 'colspan' => 3 + $gradingSubjectAssessmentCount];
        $absentRow[] = ['key' => 'extra', 'label' => '', 'colspan' => 3 + $gradingSubjectAssessmentCount];
        $failRow[] = ['key' => 'extra', 'label' => '', 'colspan' => 3 + $gradingSubjectAssessmentCount];
        $passRow[] = ['key' => 'extra', 'label' => '', 'colspan' => 3 + $gradingSubjectAssessmentCount];

        $rows[] = $highestRow;
        $rows[] = $lowestRow;
        $rows[] = $averageRow;
        $rows[] = $absentRow;
        $rows[] = $passRow;
        $rows[] = $failRow;

        $signatoryRow[] = ['key' => 'signatory', 'class' => 'font-weight-bold', 'label' => trans('academic.teacher'), 'colspan' => 3, 'height' => 40];
        foreach ($mainSubjects as $subject) {
            foreach ($subject['assessments'] as $assessment) {
                $signatoryRow[] = ['key' => 'sign', 'label' => ''];
            }
        }

        $signatoryRow[] = ['key' => 'extra', 'label' => '', 'colspan' => 3 + $gradingSubjectAssessmentCount];

        $rows[] = $signatoryRow;

        $incharges = Incharge::query()
            ->whereHasMorph(
                'model',
                [Batch::class],
                function (Builder $query) use ($batch) {
                    $query->where('id', $batch->id);
                }
            )
            ->with(['employee' => fn ($q) => $q->summary()])
            ->get();

        $batchIncharges = implode(', ', $incharges->map(function ($incharge) {
            return $incharge->employee->name;
        })->toArray());

        $titles = [
            [
                'label' => $request->query('title', trans('exam.report.mark_summary.mark_summary')),
                'align' => 'center',
                'class' => 'heading',
            ],
            [
                'label' => $exam->name,
                'align' => 'center',
                'class' => 'mt-2 sub-heading',
            ],
            [
                'label' => $batch->course->name.' '.$batch->name,
                'align' => 'center',
                'class' => 'mt-2 sub-heading',
            ],
            [
                'label' => trans('academic.class_teacher').' : '.($batchIncharges ?: '-'),
                'align' => 'center',
                'class' => 'mt-2 sub-heading',
            ],
        ];

        $layout = [
            'show_print_date_time' => $request->boolean('show_print_date_time'),
            'signatory1' => $request->query('signatory_1'),
            'signatory2' => $request->query('signatory_2'),
            'signatory3' => $request->query('signatory_3'),
            'signatory4' => $request->query('signatory_4'),
            'watermark' => $request->query('show_watermark', false),
        ];

        return view('print.exam.report.mark-summary', compact('titles', 'rows', 'header', 'subHeader', 'layout'))->render();
    }

    private function updateSubjects(array $header, array $subHeader, array $marks, array $subjects)
    {
        foreach ($subjects as $subject) {
            $subjectId = Arr::get($subject, 'id');
            $assessments = Arr::get($subject, 'assessments', []);
            $subjectMarks = Arr::get($subject, 'marks', []);

            array_push($header, [
                'key' => "subject{$subjectId}",
                'class' => 'text-center font-weight-bold',
                'colspan' => count($assessments),
                'label' => Arr::get($subject, 'shortcode'),
            ]);

            foreach ($assessments as $assessment) {

                array_push($subHeader, [
                    'key' => "subject{$subjectId}_{$assessment['code']}",
                    'class' => 'text-center font-weight-bold',
                    'label' => $assessment['code'].' ('.$assessment['max_mark'].')',
                    'text' => $assessment['name'],
                ]);

                $assessmentMarks = collect($subjectMarks)->firstWhere('code', $assessment['code']);

                $marks[] = [
                    'subject_id' => $subjectId,
                    'assessment_code' => $assessment['code'],
                    'max_mark' => $assessment['max_mark'],
                    'marks' => $assessmentMarks['marks'] ?? [],
                ];
            }
        }

        return [$header, $subHeader, $marks];
    }

    private function getStudentMarks(Student $student, Grade $grade, Collection $marks, array $subjects, array $row, array $params = [])
    {
        $subjectWiseStudents = Arr::get($params, 'subject_wise_students', collect([]));

        foreach ($subjects as $subject) {
            $notApplicableStudents = $subject['not_applicable_students'] ?? [];

            $isElective = false;

            if (Arr::get($subject, 'is_elective')) {
                $isElective = $subjectWiseStudents
                    ->where('student_id', $student->id)
                    ->where('subject_id', $subject['id'])
                    ->first() ? true : false;
            }

            if (in_array($student->uuid, $notApplicableStudents)) {
                foreach ($subject['assessments'] as $assessment) {
                    $row[] = [
                        'subject_id' => $subject['id'],
                        'assessment_code' => $assessment['code'],
                        'key' => "subject{$subject['id']}_{$assessment['code']}",
                        'label' => 'NA',
                        'class' => 'text-center',
                        'max_mark' => 0,
                        'numeric_mark' => 0,
                        'grade' => '',
                    ];
                }

                continue;
            }

            foreach ($subject['assessments'] as $assessment) {
                $assessmentMark = $marks
                    ->where('subject_id', $subject['id'])
                    ->where('assessment_code', $assessment['code'])
                    ->first();

                $mark = collect($assessmentMark['marks'] ?? []);

                $maxMark = $assessment['max_mark'] ?? 0;

                $studentMark = $mark->firstWhere('uuid', $student->uuid);
                $obtainedMark = $studentMark['obtained_mark'] ?? '';

                $label = $studentMark['obtained_mark'] ?? '';

                if ($subject['has_grading']) {
                    $label = $this->getGrade($grade, $assessment['max_mark'], $obtainedMark);
                }

                $markClass = 'text-center';
                $subjectGrade = $this->getGrade($grade, $assessment['max_mark'], $obtainedMark);

                $failGrades = Arr::get($params, 'fail_grades', []);
                if (in_array($subjectGrade, $failGrades)) {
                    $markClass = 'text-center font-weight-bold';
                }

                if ($isElective) {
                    $label .= '*';
                }

                $row[] = [
                    'subject_id' => $subject['id'],
                    'assessment_code' => $assessment['code'],
                    'key' => "subject{$subject['id']}_{$assessment['code']}",
                    'type' => 'marks',
                    'label' => $label,
                    'class' => $markClass,
                    'text-style' => in_array($subjectGrade, $failGrades) ? 'circular-border' : '',
                    'max_mark' => $maxMark,
                    'numeric_mark' => is_numeric($obtainedMark) ? $obtainedMark : 0,
                    'grade' => $subjectGrade,
                ];
            }
        }

        return $row;
    }
}
