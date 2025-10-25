<?php

namespace App\Services\Exam;

use App\Http\Resources\Exam\TermResource;
use App\Models\Exam\Exam;
use App\Models\Exam\Term;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExamService
{
    public function preRequisite(Request $request)
    {
        $terms = TermResource::collection(Term::query()
            ->with('division')
            ->byPeriod()
            ->get());

        return compact('terms');
    }

    public function create(Request $request): Exam
    {
        \DB::beginTransaction();

        $exam = Exam::forceCreate($this->formatParams($request));

        \DB::commit();

        return $exam;
    }

    private function formatParams(Request $request, ?Exam $exam = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'term_id' => $request->term_id,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ];

        if (! $exam) {
            // $formatted['position'] = $request->integer('position', 0);
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, Exam $exam): void
    {
        \DB::beginTransaction();

        $exam->forceFill($this->formatParams($request, $exam))->save();

        \DB::commit();
    }

    public function deletable(Exam $exam): bool
    {
        $examScheduleExists = \DB::table('exam_schedules')
            ->whereExamId($exam->id)
            ->exists();

        if ($examScheduleExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('exam.exam'), 'dependency' => trans('exam.schedule.schedule')])]);
        }

        return true;
    }
}
