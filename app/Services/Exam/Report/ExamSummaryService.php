<?php

namespace App\Services\Exam\Report;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Enums\Exam\AssessmentAttempt;
use App\Enums\Exam\ReportType;
use App\Enums\Exam\Result as ExamResult;
use App\Http\Resources\Exam\ExamResource;
use App\Domain\Academic\Models\Batch;
use App\Models\Exam\Exam;
use App\Models\Exam\Result;
use App\Models\Exam\Schedule;
use App\Models\Incharge;
use App\Support\HasGrade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class ExamSummaryService
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

        $request->merge([
            'select_all' => true,
        ]);

        $params = $request->all();

        if ($request->boolean('show_course_wise')) {
            $params['batch'] = $batch->course->batches->pluck('uuid')->all();
        }

        if ($request->boolean('show_all_student')) {
            $params['status'] = 'all';
        }

        $students = (new FetchBatchWiseStudent)->execute($params);

        $examResults = Result::query()
            ->whereExamId($exam->id)
            ->whereAttempt($request->attempt)
            ->whereIn('student_id', $students->pluck('id')->all())
            ->get();

        $header = [];
        if ($request->boolean('show_sno')) {
            array_push($header, ['key' => 'sno', 'class' => 'font-weight-bold', 'label' => trans('general.sno')]);
        }

        array_push($header, ['key' => 'roll_number', 'class' => 'font-weight-bold', 'label' => trans('student.roll_number.roll_number')]);
        array_push($header, ['key' => 'admission_number', 'class' => 'font-weight-bold', 'label' => trans('student.admission.props.code_number')]);
        array_push($header, ['key' => 'name', 'class' => 'font-weight-bold', 'label' => trans('student.props.name')]);

        if ($schedule->getConfig('report_type') == ReportType::CREDIT_BASED->value) {
            array_push($header, ['key' => 'total_credit', 'class' => 'font-weight-bold', 'label' => trans('exam.marksheet.total_credit')]);
            array_push($header, ['key' => 'obtained_credit', 'class' => 'font-weight-bold', 'label' => trans('exam.marksheet.obtained_credit')]);
            array_push($header, ['key' => 'gpa', 'class' => 'font-weight-bold', 'label' => trans('exam.gpa')]);
        } else {
            array_push($header, ['key' => 'total_marks', 'class' => 'font-weight-bold', 'label' => trans('exam.total_marks')]);
            array_push($header, ['key' => 'obtained_marks', 'class' => 'font-weight-bold', 'label' => trans('exam.obtained_marks')]);
            array_push($header, ['key' => 'percentage', 'class' => 'font-weight-bold', 'label' => trans('exam.percentage')]);
        }
        array_push($header, ['key' => 'result', 'class' => 'font-weight-bold', 'label' => trans('exam.result')]);

        $rows = [];
        $i = 0;
        foreach ($students as $student) {
            $examResult = $examResults->firstWhere('student_id', $student->id);

            if (! $examResult) {
                continue;
            }

            $i++;

            $result = ExamResult::getDetail($examResult->result);
            $resultLabel = Arr::get($result, 'label');

            if ($request->boolean('show_subject_detail')) {
                if ($examResult->result == ExamResult::FAIL) {
                    $resultLabel .= ' ('.implode(', ', Arr::get($examResult->subjects, 'failed', [])).')';
                } elseif ($examResult->result == ExamResult::REASSESSMENT) {
                    $resultLabel .= ' ('.implode(', ', Arr::get($examResult->subjects, 'reassessment', [])).')';
                }
            }

            $row = [];

            if ($request->boolean('show_sno')) {
                $row[] = ['key' => 'sno', 'label' => $i];
            }

            $row[] = ['key' => 'roll_number', 'label' => $student->roll_number];
            $row[] = ['key' => 'admission_number', 'label' => $student->code_number];
            $row[] = ['key' => 'name', 'label' => $student->name];
            $row[] = ['key' => 'total_marks', 'label' => $examResult->total_marks ?? ''];
            $row[] = ['key' => 'obtained_marks', 'label' => $examResult->obtained_marks ?? ''];
            $row[] = ['key' => 'percentage', 'label' => $examResult->percentage ?? ''];
            $row[] = ['key' => 'result', 'label' => $resultLabel];

            $rows[] = $row;
        }

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

        $summary = [
            'total' => $students->count(),
            'passed' => $examResults->where('result', ExamResult::PASS)->count(),
            'failed' => $examResults->where('result', ExamResult::FAIL)->count(),
            'reassessment' => $examResults->where('result', ExamResult::REASSESSMENT)->count(),
        ];

        $titles = [
            [
                'label' => $request->query('title', trans('exam.report.exam_summary.exam_summary')),
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

        return view()->first([config('config.print.custom_path').'exam.report.exam-summary', 'print.exam.report.exam-summary'], compact('titles', 'rows', 'header', 'layout', 'summary'))->render();
    }
}
