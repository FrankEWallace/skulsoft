<?php

namespace App\Services\Exam;

use App\Models\Exam\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class GradeService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    public function create(Request $request): Grade
    {
        \DB::beginTransaction();

        $grade = Grade::forceCreate($this->formatParams($request));

        \DB::commit();

        return $grade;
    }

    private function formatParams(Request $request, ?Grade $grade = null): array
    {
        $formatted = [
            'name' => $request->name,
            'records' => collect($request->records)->map(function ($record, $index) {
                return [
                    'position' => $index + 1,
                    'code' => Arr::get($record, 'code'),
                    'min_score' => Arr::get($record, 'min_score'),
                    'max_score' => Arr::get($record, 'max_score'),
                    'value' => Arr::get($record, 'value'),
                    'label' => Arr::get($record, 'label'),
                    'is_fail_grade' => (bool) Arr::get($record, 'is_fail_grade'),
                    'description' => Arr::get($record, 'description'),
                ];
            })->toArray(),
            'description' => $request->description,
        ];

        if (! $grade) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, Grade $grade): void
    {
        \DB::beginTransaction();

        $grade->forceFill($this->formatParams($request, $grade))->save();

        \DB::commit();
    }

    public function deletable(Grade $grade): bool
    {
        $examScheduleExists = \DB::table('exam_schedules')
            ->whereGradeId($grade->id)
            ->exists();

        if ($examScheduleExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('exam.grade.grade'), 'dependency' => trans('exam.schedule.schedule')])]);
        }

        return true;
    }
}
