<?php

namespace App\Services\Exam;

use App\Actions\Exam\ProcessCreditBasedMarksheet;
use App\Actions\Exam\ProcessCumulativeMarksheet;
use App\Actions\Exam\ProcessExamWiseCameroonMarksheet;
use App\Actions\Exam\ProcessExamWiseGhanaMarksheet;
use App\Actions\Exam\ProcessExamWiseMarksheet;
use App\Actions\Exam\ProcessTermWiseCameroonMarksheet;
use App\Actions\Exam\ProcessTermWiseMarksheet;
use App\Actions\Student\FetchBatchWiseStudent;
use App\Concerns\Exam\MarksheetLayout;
use App\Enums\Exam\AssessmentAttempt;
use App\Http\Resources\Exam\ExamResource;
use App\Http\Resources\Exam\TermResource;
use App\Models\Academic\Batch;
use App\Models\Exam\Exam;
use App\Models\Exam\Term;
use App\Support\HasGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarksheetProcessService
{
    use HasGrade, MarksheetLayout;

    public function preRequisite(Request $request)
    {
        $types = $this->getMarksheetTypes();

        $terms = TermResource::collection(Term::query()
            ->with('division')
            ->byPeriod()
            ->get());

        $exams = ExamResource::collection(Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->get());

        $attempts = AssessmentAttempt::getOptions();

        $templates = $this->getTemplates();

        return compact('types', 'terms', 'exams', 'attempts', 'templates');
    }

    public function process(Request $request)
    {
        $types = $this->getMarksheetTypes();
        $types = collect($types)->pluck('value')->implode(',');

        $request->validate([
            'type' => 'required|in:'.$types,
            'term' => 'uuid|required_if:type,term_wise',
            'batch' => 'required|uuid',
            'result_date' => 'required|date_format:Y-m-d',
            'template' => 'required|string',
        ], [
            'term.required_if' => trans('validation.required', ['attribute' => trans('exam.term.term')]),
        ]);

        if (Str::startsWith($request->type, 'exam_wise')) {
            $request->validate([
                'exam' => 'required|uuid',
            ]);
        }

        if (Str::startsWith($request->type, 'term_wise')) {
            $request->validate([
                'term' => 'required|uuid',
            ]);
        }

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

        $params = $request->all();
        $params['status'] = 'all';
        $params['select_all'] = true;

        $students = (new FetchBatchWiseStudent)->execute($params);

        if (in_array($request->type, ['cumulative'])) {
            (new ProcessCumulativeMarksheet)->execute($batch, $students, $params);
        } elseif (in_array($request->type, ['term_wise'])) {
            (new ProcessTermWiseMarksheet)->execute($batch, $students, $params);
        } elseif (in_array($request->type, ['exam_wise_cameroon'])) {
            (new ProcessExamWiseCameroonMarksheet)->execute($batch, $students, $params);
        } elseif (in_array($request->type, ['exam_wise_ghana'])) {
            (new ProcessExamWiseGhanaMarksheet)->execute($batch, $students, $params);
        } elseif (in_array($request->type, ['term_wise_cameroon'])) {
            (new ProcessTermWiseCameroonMarksheet)->execute($batch, $students, $params);
        } elseif (in_array($request->type, ['exam_wise_credit_based'])) {
            (new ProcessCreditBasedMarksheet)->execute($batch, $students, $params);
        } else {
            (new ProcessExamWiseMarksheet)->execute($batch, $students, $params);
        }
    }
}
